import {
	Button,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import {
	createAppPassword,
	errorMessage,
	listAppPasswords,
	revokeAppPassword,
} from '../api';
import { CodeBlock } from '../components/CodeBlock';
import { CopyField } from '../components/CopyField';
import { Section } from '../components/Section';
import { config } from '../config';
import {
	buildSnippets,
	PASSWORD_PLACEHOLDER,
	type ClientId,
} from '../snippets';
import type { AppPassword, NewAppPassword } from '../types';

const formatDate = ( value: string | null ): string =>
	value ? new Date( value ).toLocaleString() : __( 'Never', 'mcp-builder' );

export const ConnectionTab = () => {
	const [ passwords, setPasswords ] = useState< AppPassword[] | null >(
		null
	);
	const [ name, setName ] = useState( 'MCP Builder – Claude' );
	const [ created, setCreated ] = useState< NewAppPassword | null >( null );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ client, setClient ] = useState< ClientId >( 'claude-code' );

	const refresh = () =>
		listAppPasswords()
			.then( setPasswords )
			.catch( ( e ) => setError( errorMessage( e ) ) );

	useEffect( () => {
		refresh();
	}, [] );

	const create = async () => {
		setBusy( true );
		setError( null );
		try {
			setCreated(
				await createAppPassword( name.trim() || 'MCP Builder' )
			);
			await refresh();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
		setBusy( false );
	};

	const revoke = async ( item: AppPassword ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				sprintf(
					/* translators: %s: credential name. */
					__(
						'Revoke "%s"? Any AI client using it will stop working.',
						'mcp-builder'
					),
					item.name
				)
			)
		) {
			return;
		}
		setError( null );
		try {
			await revokeAppPassword( item.uuid );
			if ( created?.uuid === item.uuid ) {
				setCreated( null );
			}
			await refresh();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	const snippets = useMemo(
		() =>
			buildSnippets( {
				mcpUrl: config.mcpUrl,
				username: config.username,
				password: created?.password ?? PASSWORD_PLACEHOLDER,
			} ),
		[ created ]
	);
	const snippet =
		snippets.find( ( item ) => item.id === client ) ?? snippets[ 0 ];

	return (
		<>
			{ ! config.canRun && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'MCP Builder is not running yet. Open the Status tab to see what is missing.',
						'mcp-builder'
					) }
				</Notice>
			) }

			<Section
				title={ __( 'MCP endpoint', 'mcp-builder' ) }
				description={ __(
					'AI clients connect to this address. It only works for signed-in users who can edit posts.',
					'mcp-builder'
				) }
			>
				<CopyField
					label={ __( 'Endpoint URL', 'mcp-builder' ) }
					value={ config.mcpUrl }
				/>
			</Section>

			<Section
				title={ __( 'Connection credentials', 'mcp-builder' ) }
				description={ __(
					'AI clients sign in as you with an Application Password. They can do what your user can do, limited by the tool settings of this plugin. Create one per computer or client so you can revoke them separately.',
					'mcp-builder'
				) }
			>
				{ error && (
					<Notice status="error" onRemove={ () => setError( null ) }>
						{ error }
					</Notice>
				) }

				{ created && (
					<Notice status="success" isDismissible={ false }>
						<p>
							<strong>
								{ __(
									'Copy this password now. It will not be shown again.',
									'mcp-builder'
								) }
							</strong>
						</p>
						<CopyField
							label={ created.name }
							value={ created.password }
						/>
					</Notice>
				) }

				<div className="mcpb-inline-form">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Name', 'mcp-builder' ) }
						value={ name }
						onChange={ setName }
					/>
					<Button
						variant="primary"
						onClick={ create }
						isBusy={ busy }
						disabled={ busy }
					>
						{ __( 'Create credentials', 'mcp-builder' ) }
					</Button>
				</div>

				{ passwords === null ? (
					<Spinner />
				) : (
					<table className="widefat striped mcpb-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Name', 'mcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Created', 'mcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Last used', 'mcp-builder' ) }
								</th>
								<th scope="col">
									<span className="screen-reader-text">
										{ __( 'Actions', 'mcp-builder' ) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ passwords.length === 0 && (
								<tr>
									<td colSpan={ 4 }>
										{ __(
											'No credentials yet.',
											'mcp-builder'
										) }
									</td>
								</tr>
							) }
							{ passwords.map( ( item ) => (
								<tr key={ item.uuid }>
									<td>{ item.name }</td>
									<td>{ formatDate( item.created ) }</td>
									<td>{ formatDate( item.last_used ) }</td>
									<td>
										<Button
											variant="link"
											isDestructive
											onClick={ () => revoke( item ) }
										>
											{ __( 'Revoke', 'mcp-builder' ) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</Section>

			<Section
				title={ __( 'Set up your AI client', 'mcp-builder' ) }
				description={
					created
						? __(
								'These snippets contain the password you just created. Keep them private and never commit them to a repository.',
								'mcp-builder'
							)
						: sprintf(
								/* translators: %s: placeholder text. */
								__(
									'Create credentials above to fill in your password, or replace %s yourself.',
									'mcp-builder'
								),
								PASSWORD_PLACEHOLDER
							)
				}
			>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Client', 'mcp-builder' ) }
					value={ client }
					options={ snippets.map( ( item ) => ( {
						label: item.label,
						value: item.id,
					} ) ) }
					onChange={ ( value ) => setClient( value as ClientId ) }
				/>
				<p className="mcpb-help">{ snippet.where }</p>
				<CodeBlock code={ snippet.code } label={ snippet.label } />
				{ config.isLocal && (
					<p className="mcpb-help">
						{ __(
							"Local site: if your client cannot connect over https, trust LocalWP's certificate (Site → SSL → Trust) or use the http:// address.",
							'mcp-builder'
						) }
					</p>
				) }
			</Section>
		</>
	);
};
