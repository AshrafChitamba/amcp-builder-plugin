export type ClientId = 'claude-code' | 'cursor' | 'claude-desktop';

export interface SnippetInput {
	mcpUrl: string;
	username: string;
	/** Application Password, or a placeholder when none was just created. */
	password: string;
}

export interface Snippet {
	id: ClientId;
	label: string;
	/** Where the snippet goes, shown above it. */
	where: string;
	language: 'bash' | 'json';
	code: string;
}

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
 * Ready-to-paste configuration for each supported client.
 *
 * @param input Endpoint and credentials to embed.
 */
export const buildSnippets = ( input: SnippetInput ): Snippet[] => {
	const { mcpUrl, username, password } = input;
	const authorization = basicAuth( username, password );

	return [
		{
			id: 'claude-code',
			label: 'Claude Code',
			where: 'Run in a terminal:',
			language: 'bash',
			code: `claude mcp add --transport http --scope user ${ SERVER_NAME } ${ mcpUrl } --header "Authorization: ${ authorization }"`,
		},
		{
			id: 'cursor',
			label: 'Cursor',
			where: 'Add to ~/.cursor/mcp.json:',
			language: 'json',
			code: json( {
				mcpServers: {
					[ SERVER_NAME ]: {
						url: mcpUrl,
						headers: { Authorization: authorization },
					},
				},
			} ),
		},
		{
			id: 'claude-desktop',
			label: 'Claude Desktop',
			where: 'Add to claude_desktop_config.json (Settings → Developer → Edit config). Needs Node.js on this computer:',
			language: 'json',
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
							WP_API_PASSWORD: password,
						},
					},
				},
			} ),
		},
	];
};
