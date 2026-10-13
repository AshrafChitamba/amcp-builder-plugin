export type ClientId =
	| 'claude-code'
	| 'codex'
	| 'cursor'
	| 'vscode'
	| 'gemini'
	| 'claude-desktop-proxy';

export type AuthMode = 'oauth' | 'token';

export interface SnippetInput {
	mcpUrl: string;
	/** Access token, or a placeholder when none was just created. */
	token: string;
	auth: AuthMode;
	/** For the Claude Desktop proxy, which uses an Application Password. */
	username: string;
	appPassword: string;
}

export interface Snippet {
	id: ClientId;
	label: string;
	/** Where the snippet goes, shown above it. */
	where: string;
	/** Follow-up step, e.g. how to sign in. */
	after?: string;
	language: 'bash' | 'json' | 'toml';
	code: string;
	/** True when this client can sign in with WordPress (OAuth). */
	oauth: boolean;
}

export const TOKEN_PLACEHOLDER = 'YOUR-ACCESS-TOKEN';
export const PASSWORD_PLACEHOLDER = 'YOUR-APPLICATION-PASSWORD';

const SERVER_NAME = 'mcp-builder';

/**
 * Value for an HTTP Basic Authorization header.
 *
 * @param username WordPress username.
 * @param password Application Password.
 */
export const basicAuth = ( username: string, password: string ): string => {
	const bytes = new TextEncoder().encode( `${ username }:${ password }` );
	let binary = '';
	bytes.forEach( ( byte ) => ( binary += String.fromCharCode( byte ) ) );
	return `Basic ${ btoa( binary ) }`;
};

const json = ( value: unknown ): string => JSON.stringify( value, null, 2 );

/**
 * Ready-to-paste configuration for each supported coding client.
 *
 * @param input Endpoint, credentials and auth mode.
 */
export const buildSnippets = ( input: SnippetInput ): Snippet[] => {
	const { mcpUrl, token, auth, username, appPassword } = input;
	const bearer = `Bearer ${ token }`;
	const oauth = auth === 'oauth';

	return [
		{
			id: 'claude-code',
			label: 'Claude Code',
			where: 'Run in a terminal:',
			after: oauth
				? 'Then run /mcp inside Claude Code and choose "Authenticate". Your browser opens the WordPress approval page.'
				: undefined,
			language: 'bash',
			oauth: true,
			code: oauth
				? `claude mcp add --transport http --scope user ${ SERVER_NAME } ${ mcpUrl }`
				: `claude mcp add --transport http --scope user ${ SERVER_NAME } ${ mcpUrl } --header "Authorization: ${ bearer }"`,
		},
		{
			id: 'codex',
			label: 'OpenAI Codex (CLI, IDE, desktop app)',
			where: oauth
				? 'Run in a terminal:'
				: 'Add to ~/.codex/config.toml, then set the token in your shell (export MCP_BUILDER_TOKEN=...):',
			after: oauth
				? 'Your browser opens the WordPress approval page.'
				: undefined,
			language: oauth ? 'bash' : 'toml',
			oauth: true,
			code: oauth
				? `codex mcp add ${ SERVER_NAME } --url ${ mcpUrl }\ncodex mcp login ${ SERVER_NAME }`
				: `[mcp_servers.${ SERVER_NAME.replace(
						'-',
						'_'
					) }]\nurl = "${ mcpUrl }"\nbearer_token_env_var = "MCP_BUILDER_TOKEN"\n\n# export MCP_BUILDER_TOKEN="${ token }"`,
		},
		{
			id: 'cursor',
			label: 'Cursor',
			where: 'Add to ~/.cursor/mcp.json:',
			after: oauth
				? 'Cursor shows a "Needs login" button for the server; click it to approve in WordPress.'
				: undefined,
			language: 'json',
			oauth: true,
			code: json( {
				mcpServers: {
					[ SERVER_NAME ]: oauth
						? { url: mcpUrl }
						: { url: mcpUrl, headers: { Authorization: bearer } },
				},
			} ),
		},
		{
			id: 'vscode',
			label: 'VS Code (GitHub Copilot)',
			where: 'Add to .vscode/mcp.json (or your user mcp.json):',
			language: 'json',
			oauth: true,
			code: json(
				oauth
					? {
							servers: {
								[ SERVER_NAME ]: { type: 'http', url: mcpUrl },
							},
						}
					: {
							servers: {
								[ SERVER_NAME ]: {
									type: 'http',
									url: mcpUrl,
									headers: {
										Authorization:
											'Bearer ${input:mcp-builder-token}',
									},
								},
							},
							inputs: [
								{
									type: 'promptString',
									id: 'mcp-builder-token',
									description: 'MCP Builder access token',
									password: true,
								},
							],
						}
			),
		},
		{
			id: 'gemini',
			label: 'Gemini CLI',
			where: 'Run in a terminal:',
			after: oauth
				? 'Gemini CLI asks you to approve in WordPress on first use.'
				: undefined,
			language: 'bash',
			oauth: true,
			code: oauth
				? `gemini mcp add --transport http ${ SERVER_NAME } ${ mcpUrl }`
				: `gemini mcp add --transport http --header "Authorization: ${ bearer }" ${ SERVER_NAME } ${ mcpUrl }`,
		},
		{
			id: 'claude-desktop-proxy',
			label: 'Claude Desktop (local site, via proxy)',
			where: 'For sites that are not reachable over public HTTPS (e.g. LocalWP). Add to claude_desktop_config.json (Settings → Developer → Edit config). Needs Node.js on this computer:',
			language: 'json',
			oauth: false,
			code: json( {
				mcpServers: {
					[ SERVER_NAME ]: {
						command: 'npx',
						args: [
							'-y',
							'@automattic/mcp-wordpress-remote@latest',
						],
						env: {
							WP_API_URL: mcpUrl,
							WP_API_USERNAME: username,
							WP_API_PASSWORD: appPassword,
						},
					},
				},
			} ),
		},
	];
};
