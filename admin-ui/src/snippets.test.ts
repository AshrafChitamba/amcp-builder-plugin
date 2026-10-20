import { describe, expect, it } from 'vitest';

import { basicAuth, buildSnippets, TOKEN_PLACEHOLDER } from './snippets';

const base = {
	mcpUrl: 'https://example.test/wp-json/amcp-builder/mcp',
	token: 'amcpbp_abc123',
	username: 'ashraf',
	appPassword: 'abcd efgh ijkl',
};

const byId = ( auth: 'oauth' | 'token', id: string ) =>
	buildSnippets( { ...base, auth } ).find( ( item ) => item.id === id )!;

describe( 'basicAuth', () => {
	it( 'encodes username and password as UTF-8', () => {
		expect( basicAuth( 'user', 'pass' ) ).toBe( 'Basic dXNlcjpwYXNz' );
		expect( basicAuth( 'zoë', 'x' ) ).toBe(
			`Basic ${ Buffer.from( 'zoë:x', 'utf8' ).toString( 'base64' ) }`
		);
	} );
} );

describe( 'buildSnippets', () => {
	it( 'offers every supported coding client', () => {
		expect(
			buildSnippets( { ...base, auth: 'token' } ).map(
				( item ) => item.id
			)
		).toEqual( [
			'claude-code',
			'codex',
			'cursor',
			'vscode',
			'gemini',
			'claude-desktop-proxy',
		] );
	} );

	it( 'puts the bearer token in header-based snippets', () => {
		expect( byId( 'token', 'claude-code' ).code ).toContain(
			'--header "Authorization: Bearer amcpbp_abc123"'
		);
		expect(
			JSON.parse( byId( 'token', 'cursor' ).code ).mcpServers[
				'amcp-builder'
			].headers.Authorization
		).toBe( 'Bearer amcpbp_abc123' );
		expect( byId( 'token', 'gemini' ).code ).toContain(
			'Authorization: Bearer amcpbp_abc123'
		);
	} );

	it( 'leaves credentials out of OAuth snippets', () => {
		for ( const id of [
			'claude-code',
			'codex',
			'cursor',
			'vscode',
			'gemini',
		] ) {
			const snippet = byId( 'oauth', id );
			expect( snippet.code ).not.toContain( 'amcpbp_abc123' );
			expect( snippet.code ).toContain( base.mcpUrl );
		}
		expect( byId( 'oauth', 'codex' ).code ).toContain(
			'codex mcp login amcp-builder'
		);
	} );

	it( 'uses an environment variable for Codex tokens', () => {
		const code = byId( 'token', 'codex' ).code;
		expect( code ).toContain( '[mcp_servers.amcp_builder]' );
		expect( code ).toContain(
			'bearer_token_env_var = "AMCP_BUILDER_TOKEN"'
		);
	} );

	it( 'never writes the VS Code token into the file', () => {
		const config = JSON.parse( byId( 'token', 'vscode' ).code );
		expect( config.servers[ 'amcp-builder' ].headers.Authorization ).toBe(
			'Bearer ${input:amcp-builder-token}'
		);
		expect( config.inputs[ 0 ].password ).toBe( true );
	} );

	it( 'configures the Claude Desktop proxy with an Application Password', () => {
		const server = JSON.parse(
			byId( 'token', 'claude-desktop-proxy' ).code
		).mcpServers[ 'amcp-builder' ];
		expect( server.env ).toEqual( {
			WP_API_URL: base.mcpUrl,
			WP_API_USERNAME: base.username,
			WP_API_PASSWORD: base.appPassword,
		} );
	} );

	it( 'keeps the placeholder visible when no token was created', () => {
		const snippet = buildSnippets( {
			...base,
			auth: 'token',
			token: TOKEN_PLACEHOLDER,
		} )[ 0 ];
		expect( snippet.code ).toContain( TOKEN_PLACEHOLDER );
	} );
} );
