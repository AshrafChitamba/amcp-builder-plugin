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
	value ? new Date( value ).toLocaleString() : __( 'Never', 'ac-mcp-for-bricks-elementor' );

const isHttps = config.mcpUrl.startsWith( 'https://' );

const DISCOVERY_LABELS: Record< string, string > = {
	resourceMetadata: __( 'Resource metadata (REST)', 'ac-mcp-for-bricks-elementor' ),
	wellKnownResource: __( 'Resource metadata (/.well-known)', 'ac-mcp-for-bricks-elementor' ),
	wellKnownServer: __(
		'Authorization server (/.well-known)',
		'ac-mcp-for-bricks-elementor'
	),
	openidAppended: __( 'Authorization server (REST)', 'ac-mcp-for-bricks-elementor' ),
	https: __( 'HTTPS', 'ac-mcp-for-bricks-elementor' ),
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
				'ac-mcp-for-bricks-elementor'
			) }
			description={ __(
				'These apps connect from the internet and sign in with your WordPress account. No password or token to copy.',
				'ac-mcp-for-bricks-elementor'
			) }
		>
			{ ( ! isHttps || config.isLocal ) && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'This site is not reachable from the internet over HTTPS, so web apps cannot connect to it. Use it on a live site, expose a local site with a tunnel (LocalWP Live Link or Cloudflare Tunnel), or use a coding tool below.',
						'ac-mcp-for-bricks-elementor'
					) }
				</Notice>
			) }
			<h3 className="acmcp-subheading">claude.ai and Claude Desktop</h3>
			<ol className="acmcp-steps">
				<li>
					{ __(
						'Open Customize → Connectors → "Add custom connector".',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
				<li>
					{ __(
						'Paste the endpoint URL above. Leave the OAuth client fields empty.',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
				<li>
					{ __(
						'Click Connect, sign in to WordPress if asked, choose the access level and click "Allow access".',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
			</ol>
			<p className="acmcp-help">
				{ __(
					'Available on all Claude plans; the Free plan allows one custom connector.',
					'ac-mcp-for-bricks-elementor'
				) }
			</p>
			<h3 className="acmcp-subheading">ChatGPT</h3>
			<ol className="acmcp-steps">
				<li>
					{ __(
						'Open Settings → Apps (or Plugins) and choose "Add custom MCP server". You may need to turn on Developer mode first.',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
				<li>
					{ __(
						'Paste the endpoint URL and choose OAuth as the authentication.',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
				<li>
					{ __(
						'Approve the connection on the WordPress page that opens.',
						'ac-mcp-for-bricks-elementor'
					) }
				</li>
			</ol>
			<p className="acmcp-help">
				{ __(
					'Custom MCP servers depend on your ChatGPT plan and workspace settings; they are not available on every plan.',
					'ac-mcp-for-bricks-elementor'
				) }
			</p>
			<div className="acmcp-test">
				<Button
					variant="secondary"
					onClick={ run }
					isBusy={ busy }
					disabled={ busy }
				>
					{ __( 'Test sign-in discovery', 'ac-mcp-for-bricks-elementor' ) }
				</Button>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ result && (
					<ul className="acmcp-checklist" aria-live="polite">
						{ Object.entries( result ).map( ( [ key, check ] ) => (
							<li key={ key }>
								<span
									className={ `acmcp-badge acmcp-badge--${
										check.ok ? 'ok' : 'warning'
									}` }
								>
									{ check.ok
										? __( 'OK', 'ac-mcp-for-bricks-elementor' )
										: __( 'Problem', 'ac-mcp-for-bricks-elementor' ) }
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
						<p className="acmcp-help">
							{ __(
								'The /.well-known addresses do not reach WordPress on this server. Most apps still connect through the REST address; if one fails, see Troubleshooting → "OAuth discovery".',
								'ac-mcp-for-bricks-elementor'
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
			title={ __( 'Connect coding tools', 'ac-mcp-for-bricks-elementor' ) }
			description={ __(
				'Claude Code, OpenAI Codex, Cursor, VS Code and Gemini CLI can sign in with WordPress, or use an access token.',
				'ac-mcp-for-bricks-elementor'
			) }
		>
			<RadioControl
				label={ __( 'How to sign in', 'ac-mcp-for-bricks-elementor' ) }
				selected={ auth }
				options={ [
					{
						label: __(
							'Sign in with WordPress (recommended)',
							'ac-mcp-for-bricks-elementor'
						),
						value: 'oauth',
					},
					{
						label: __( 'Access token', 'ac-mcp-for-bricks-elementor' ),
						value: 'token',
					},
				] }
				onChange={ ( value ) => setAuth( value as AuthMode ) }
			/>
			{ auth === 'oauth' && ! isHttps && ! config.isLocal && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Sign-in needs HTTPS on sites reachable from the internet. Use an access token, or enable HTTPS.',
						'ac-mcp-for-bricks-elementor'
					) }
				</Notice>
			) }
			{ auth === 'token' && (
				<div className="acmcp-token-form">
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
										'ac-mcp-for-bricks-elementor'
									) }
								</strong>
							</p>
							<CopyField label={ name } value={ token } />
						</Notice>
					) }
					<div className="acmcp-inline-form">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Token name', 'ac-mcp-for-bricks-elementor' ) }
							value={ name }
							onChange={ setName }
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Expires after', 'ac-mcp-for-bricks-elementor' ) }
							value={ days }
							options={ [
								{
									value: '30',
									label: __( '30 days', 'ac-mcp-for-bricks-elementor' ),
								},
								{
									value: '90',
									label: __( '90 days', 'ac-mcp-for-bricks-elementor' ),
								},
								{
									value: '365',
									label: __( '1 year', 'ac-mcp-for-bricks-elementor' ),
								},
								{
									value: '0',
									label: __( 'Never', 'ac-mcp-for-bricks-elementor' ),
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
							{ __( 'Create token', 'ac-mcp-for-bricks-elementor' ) }
						</Button>
					</div>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Read only', 'ac-mcp-for-bricks-elementor' ) }
						help={ __(
							'Apps using this token can read pages but not change them.',
							'ac-mcp-for-bricks-elementor'
						) }
						checked={ readOnly }
						onChange={ setReadOnly }
					/>
				</div>
			) }
			<div className="acmcp-spacer" />
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Tool', 'ac-mcp-for-bricks-elementor' ) }
				value={ client }
				options={ snippets.map( ( item ) => ( {
					label: item.label,
					value: item.id,
				} ) ) }
				onChange={ ( value ) => setClient( value as ClientId ) }
			/>
			<p className="acmcp-help">{ snippet.where }</p>
			<CodeBlock code={ snippet.code } label={ snippet.label } />
			{ snippet.after && <p className="acmcp-help">{ snippet.after }</p> }
			{ auth === 'token' && token && (
				<p className="acmcp-help">
					{ __(
						'This snippet contains your token. Keep it private and never commit it to a repository.',
						'ac-mcp-for-bricks-elementor'
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
				await createAppPassword( 'AC MCP for Bricks and Elementor – Claude Desktop' )
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
			title={ __( 'Claude Desktop with a local site', 'ac-mcp-for-bricks-elementor' ) }
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
								'ac-mcp-for-bricks-elementor'
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
				{ __( 'Create Application Password', 'ac-mcp-for-bricks-elementor' ) }
			</Button>
			<CodeBlock code={ snippet.code } label={ snippet.label } />
			{ passwords === null ? (
				<Spinner />
			) : (
				passwords.length > 0 && (
					<ul className="acmcp-tool-list">
						{ passwords.map( ( item ) => (
							<li key={ item.uuid }>
								{ item.name } · { formatDate( item.last_used ) }{ ' ' }
								<Button
									variant="link"
									isDestructive
									onClick={ () => revoke( item ) }
								>
									{ __( 'Revoke', 'ac-mcp-for-bricks-elementor' ) }
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
						'ac-mcp-for-bricks-elementor'
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
			title={ __( 'Connected apps and access tokens', 'ac-mcp-for-bricks-elementor' ) }
		>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			{ ! items ? (
				<Spinner />
			) : (
				<div className="acmcp-table-wrap">
					<table className="acmcp-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Name', 'ac-mcp-for-bricks-elementor' ) }
								</th>
								<th scope="col">
									{ __( 'Type', 'ac-mcp-for-bricks-elementor' ) }
								</th>
								<th scope="col">
									{ __( 'User', 'ac-mcp-for-bricks-elementor' ) }
								</th>
								<th scope="col">
									{ __( 'Access', 'ac-mcp-for-bricks-elementor' ) }
								</th>
								<th scope="col">
									{ __( 'Last used', 'ac-mcp-for-bricks-elementor' ) }
								</th>
								<th scope="col">
									<span className="screen-reader-text">
										{ __( 'Actions', 'ac-mcp-for-bricks-elementor' ) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ items.length === 0 && (
								<tr>
									<td colSpan={ 6 } className="acmcp-empty">
										{ __(
											'Nothing connected yet.',
											'ac-mcp-for-bricks-elementor'
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
													'ac-mcp-for-bricks-elementor'
												)
											: __(
													'Access token',
													'ac-mcp-for-bricks-elementor'
												) }
									</td>
									<td>{ item.user }</td>
									<td>
										{ item.readOnly
											? __( 'Read only', 'ac-mcp-for-bricks-elementor' )
											: __(
													'Read and edit',
													'ac-mcp-for-bricks-elementor'
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
												'ac-mcp-for-bricks-elementor'
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
						'AC MCP for Bricks and Elementor is not running yet. Open the Status tab to see what is missing.',
						'ac-mcp-for-bricks-elementor'
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
