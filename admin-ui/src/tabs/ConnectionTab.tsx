import {
	Button,
	Notice,
	RadioControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import {
	createAccessToken,
	createAppPassword,
	errorMessage,
	getConnections,
	listAppPasswords,
	revokeAppPassword,
	revokeConnection,
	testDiscovery,
} from '../api';
import { CodeBlock } from '../components/CodeBlock';
import { CopyField } from '../components/CopyField';
import { Section } from '../components/Section';
import { config } from '../config';
import {
	buildSnippets,
	PASSWORD_PLACEHOLDER,
	TOKEN_PLACEHOLDER,
	type AuthMode,
	type ClientId,
} from '../snippets';
import type {
	AppPassword,
	Connection,
	DiscoveryResult,
	NewAppPassword,
} from '../types';

const formatDate = ( value: string | null ): string =>
	value ? new Date( value ).toLocaleString() : __( 'Never', 'amcp-builder' );

const isHttps = config.mcpUrl.startsWith( 'https://' );

const DISCOVERY_LABELS: Record< string, string > = {
	resourceMetadata: __( 'Resource metadata (REST)', 'amcp-builder' ),
	wellKnownResource: __( 'Resource metadata (/.well-known)', 'amcp-builder' ),
	wellKnownServer: __(
		'Authorization server (/.well-known)',
		'amcp-builder'
	),
	openidAppended: __( 'Authorization server (REST)', 'amcp-builder' ),
	https: __( 'HTTPS', 'amcp-builder' ),
};

const RemoteApps = () => {
	const [ result, setResult ] = useState< DiscoveryResult | null >( null );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const run = async () => {
		setBusy( true );
		setError( null );
		try {
			setResult( await testDiscovery() );
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
		setBusy( false );
	};

	return (
		<Section
			title={ __(
				'Connect claude.ai, Claude Desktop or ChatGPT',
				'amcp-builder'
			) }
			description={ __(
				'These apps connect from the internet and sign in with your WordPress account. No password or token to copy.',
				'amcp-builder'
			) }
		>
			{ ( ! isHttps || config.isLocal ) && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'This site is not reachable from the internet over HTTPS, so web apps cannot connect to it. Use it on a live site, expose a local site with a tunnel (LocalWP Live Link or Cloudflare Tunnel), or use a coding tool below.',
						'amcp-builder'
					) }
				</Notice>
			) }
			<h3 className="amcpb-subheading">claude.ai and Claude Desktop</h3>
			<ol className="amcpb-steps">
				<li>
					{ __(
						'Open Customize → Connectors → "Add custom connector".',
						'amcp-builder'
					) }
				</li>
				<li>
					{ __(
						'Paste the endpoint URL above. Leave the OAuth client fields empty.',
						'amcp-builder'
					) }
				</li>
				<li>
					{ __(
						'Click Connect, sign in to WordPress if asked, choose the access level and click "Allow access".',
						'amcp-builder'
					) }
				</li>
			</ol>
			<p className="amcpb-help">
				{ __(
					'Available on all Claude plans; the Free plan allows one custom connector.',
					'amcp-builder'
				) }
			</p>
			<h3 className="amcpb-subheading">ChatGPT</h3>
			<ol className="amcpb-steps">
				<li>
					{ __(
						'Open Settings → Apps (or Plugins) and choose "Add custom MCP server". You may need to turn on Developer mode first.',
						'amcp-builder'
					) }
				</li>
				<li>
					{ __(
						'Paste the endpoint URL and choose OAuth as the authentication.',
						'amcp-builder'
					) }
				</li>
				<li>
					{ __(
						'Approve the connection on the WordPress page that opens.',
						'amcp-builder'
					) }
				</li>
			</ol>
			<p className="amcpb-help">
				{ __(
					'Custom MCP servers depend on your ChatGPT plan and workspace settings; they are not available on every plan.',
					'amcp-builder'
				) }
			</p>
			<div className="amcpb-test">
				<Button
					variant="secondary"
					onClick={ run }
					isBusy={ busy }
					disabled={ busy }
				>
					{ __( 'Test sign-in discovery', 'amcp-builder' ) }
				</Button>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ result && (
					<ul className="amcpb-checklist" aria-live="polite">
						{ Object.entries( result ).map( ( [ key, check ] ) => (
							<li key={ key }>
								<span
									className={ `amcpb-badge amcpb-badge--${
										check.ok ? 'ok' : 'warning'
									}` }
								>
									{ check.ok
										? __( 'OK', 'amcp-builder' )
										: __( 'Problem', 'amcp-builder' ) }
								</span>{ ' ' }
								{ DISCOVERY_LABELS[ key ] ?? key }{ ' ' }
								<code>{ check.url }</code>
							</li>
						) ) }
					</ul>
				) }
				{ result &&
					! result.wellKnownResource?.ok &&
					result.resourceMetadata?.ok && (
						<p className="amcpb-help">
							{ __(
								'The /.well-known addresses do not reach WordPress on this server. Most apps still connect through the REST address; if one fails, see Troubleshooting → "OAuth discovery".',
								'amcp-builder'
							) }
						</p>
					) }
			</div>
		</Section>
	);
};

const CodingTools = ( { onTokenCreated }: { onTokenCreated: () => void } ) => {
	const [ auth, setAuth ] = useState< AuthMode >( 'oauth' );
	const [ client, setClient ] = useState< ClientId >( 'claude-code' );
	const [ name, setName ] = useState( 'Codex on my laptop' );
	const [ readOnly, setReadOnly ] = useState( false );
	const [ days, setDays ] = useState< '0' | '30' | '90' | '365' >( '90' );
	const [ token, setToken ] = useState< string | null >( null );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const create = async () => {
		setBusy( true );
		setError( null );
		try {
			const result = await createAccessToken( {
				name: name.trim() || 'Access token',
				readOnly,
				days: Number( days ),
			} );
			setToken( result.token );
			onTokenCreated();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
		setBusy( false );
	};

	const snippets = useMemo(
		() =>
			buildSnippets( {
				mcpUrl: config.mcpUrl,
				token: token ?? TOKEN_PLACEHOLDER,
				auth,
				username: config.username,
				appPassword: PASSWORD_PLACEHOLDER,
			} ).filter( ( item ) => item.id !== 'claude-desktop-proxy' ),
		[ token, auth ]
	);
	const snippet =
		snippets.find( ( item ) => item.id === client ) ?? snippets[ 0 ];

	return (
		<Section
			title={ __( 'Connect coding tools', 'amcp-builder' ) }
			description={ __(
				'Claude Code, OpenAI Codex, Cursor, VS Code and Gemini CLI can sign in with WordPress, or use an access token.',
				'amcp-builder'
			) }
		>
			<RadioControl
				label={ __( 'How to sign in', 'amcp-builder' ) }
				selected={ auth }
				options={ [
					{
						label: __(
							'Sign in with WordPress (recommended)',
							'amcp-builder'
						),
						value: 'oauth',
					},
					{
						label: __( 'Access token', 'amcp-builder' ),
						value: 'token',
					},
				] }
				onChange={ ( value ) => setAuth( value as AuthMode ) }
			/>
			{ auth === 'oauth' && ! isHttps && ! config.isLocal && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Sign-in needs HTTPS on sites reachable from the internet. Use an access token, or enable HTTPS.',
						'amcp-builder'
					) }
				</Notice>
			) }
			{ auth === 'token' && (
				<div className="amcpb-token-form">
					{ error && (
						<Notice
							status="error"
							onRemove={ () => setError( null ) }
						>
							{ error }
						</Notice>
					) }
					{ token && (
						<Notice status="success" isDismissible={ false }>
							<p>
								<strong>
									{ __(
										'Copy this token now. It will not be shown again.',
										'amcp-builder'
									) }
								</strong>
							</p>
							<CopyField label={ name } value={ token } />
						</Notice>
					) }
					<div className="amcpb-inline-form">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Token name', 'amcp-builder' ) }
							value={ name }
							onChange={ setName }
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Expires after', 'amcp-builder' ) }
							value={ days }
							options={ [
								{
									value: '30',
									label: __( '30 days', 'amcp-builder' ),
								},
								{
									value: '90',
									label: __( '90 days', 'amcp-builder' ),
								},
								{
									value: '365',
									label: __( '1 year', 'amcp-builder' ),
								},
								{
									value: '0',
									label: __( 'Never', 'amcp-builder' ),
								},
							] }
							onChange={ setDays }
						/>
						<Button
							variant="primary"
							onClick={ create }
							isBusy={ busy }
							disabled={ busy }
						>
							{ __( 'Create token', 'amcp-builder' ) }
						</Button>
					</div>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Read only', 'amcp-builder' ) }
						help={ __(
							'Apps using this token can read pages but not change them.',
							'amcp-builder'
						) }
						checked={ readOnly }
						onChange={ setReadOnly }
					/>
				</div>
			) }
			<div className="amcpb-spacer" />
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Tool', 'amcp-builder' ) }
				value={ client }
				options={ snippets.map( ( item ) => ( {
					label: item.label,
					value: item.id,
				} ) ) }
				onChange={ ( value ) => setClient( value as ClientId ) }
			/>
			<p className="amcpb-help">{ snippet.where }</p>
			<CodeBlock code={ snippet.code } label={ snippet.label } />
			{ snippet.after && <p className="amcpb-help">{ snippet.after }</p> }
			{ auth === 'token' && token && (
				<p className="amcpb-help">
					{ __(
						'This snippet contains your token. Keep it private and never commit it to a repository.',
						'amcp-builder'
					) }
				</p>
			) }
		</Section>
	);
};

const LocalProxy = () => {
	const [ passwords, setPasswords ] = useState< AppPassword[] | null >(
		null
	);
	const [ created, setCreated ] = useState< NewAppPassword | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const refresh = useCallback(
		() =>
			listAppPasswords()
				.then( setPasswords )
				.catch( ( e ) => setError( errorMessage( e ) ) ),
		[]
	);
	useEffect( () => {
		refresh();
	}, [ refresh ] );

	const create = async () => {
		setError( null );
		try {
			setCreated(
				await createAppPassword( 'AMCP Builder – Claude Desktop' )
			);
			await refresh();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	const revoke = async ( item: AppPassword ) => {
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

	const snippet = buildSnippets( {
		mcpUrl: config.mcpUrl,
		token: TOKEN_PLACEHOLDER,
		auth: 'token',
		username: config.username,
		appPassword: created?.password ?? PASSWORD_PLACEHOLDER,
	} ).find( ( item ) => item.id === 'claude-desktop-proxy' )!;

	return (
		<Section
			title={ __( 'Claude Desktop with a local site', 'amcp-builder' ) }
			description={ snippet.where }
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
								'amcp-builder'
							) }
						</strong>
					</p>
					<CopyField
						label={ created.name }
						value={ created.password }
					/>
				</Notice>
			) }
			<Button variant="secondary" onClick={ create }>
				{ __( 'Create Application Password', 'amcp-builder' ) }
			</Button>
			<CodeBlock code={ snippet.code } label={ snippet.label } />
			{ passwords === null ? (
				<Spinner />
			) : (
				passwords.length > 0 && (
					<ul className="amcpb-tool-list">
						{ passwords.map( ( item ) => (
							<li key={ item.uuid }>
								{ item.name } · { formatDate( item.last_used ) }{ ' ' }
								<Button
									variant="link"
									isDestructive
									onClick={ () => revoke( item ) }
								>
									{ __( 'Revoke', 'amcp-builder' ) }
								</Button>
							</li>
						) ) }
					</ul>
				)
			) }
		</Section>
	);
};

const Connections = ( { version }: { version: number } ) => {
	const [ items, setItems ] = useState< Connection[] | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( () => {
		getConnections()
			.then( setItems )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );
	useEffect( load, [ load, version ] );

	const revoke = async ( item: Connection ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				sprintf(
					/* translators: %s: app or token name. */
					__(
						'Disconnect "%s"? It stops working immediately.',
						'amcp-builder'
					),
					item.name
				)
			)
		) {
			return;
		}
		try {
			await revokeConnection( item.id );
			load();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	return (
		<Section
			title={ __( 'Connected apps and access tokens', 'amcp-builder' ) }
		>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			{ ! items ? (
				<Spinner />
			) : (
				<div className="amcpb-table-wrap">
					<table className="amcpb-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Name', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Type', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'User', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Access', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Last used', 'amcp-builder' ) }
								</th>
								<th scope="col">
									<span className="screen-reader-text">
										{ __( 'Actions', 'amcp-builder' ) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ items.length === 0 && (
								<tr>
									<td colSpan={ 6 } className="amcpb-empty">
										{ __(
											'Nothing connected yet.',
											'amcp-builder'
										) }
									</td>
								</tr>
							) }
							{ items.map( ( item ) => (
								<tr key={ item.id }>
									<td>{ item.name }</td>
									<td>
										{ item.kind === 'app'
											? __(
													'App (signed in)',
													'amcp-builder'
												)
											: __(
													'Access token',
													'amcp-builder'
												) }
									</td>
									<td>{ item.user }</td>
									<td>
										{ item.readOnly
											? __( 'Read only', 'amcp-builder' )
											: __(
													'Read and edit',
													'amcp-builder'
												) }
									</td>
									<td>{ formatDate( item.lastUsedAt ) }</td>
									<td>
										<Button
											variant="link"
											isDestructive
											onClick={ () => revoke( item ) }
										>
											{ __(
												'Disconnect',
												'amcp-builder'
											) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</Section>
	);
};

export const ConnectionTab = () => {
	const [ version, setVersion ] = useState( 0 );

	return (
		<>
			{ ! config.canRun && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'AMCP Builder is not running yet. Open the Status tab to see what is missing.',
						'amcp-builder'
					) }
				</Notice>
			) }
			<RemoteApps />
			<CodingTools onTokenCreated={ () => setVersion( version + 1 ) } />
			<LocalProxy />
			<Connections version={ version } />
		</>
	);
};
