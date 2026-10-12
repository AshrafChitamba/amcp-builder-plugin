import { describe, expect, it } from 'vitest';

import { basicAuth, buildSnippets, PASSWORD_PLACEHOLDER } from './snippets';

const input = {
	mcpUrl: 'https://example.test/wp-json/mcp-builder/mcp',
	username: 'ashraf',
	password: 'abcd efgh ijkl mnop qrst uvwx',
};

describe( 'basicAuth', () => {
	it( 'encodes username and password', () => {
		expect( basicAuth( 'user', 'pass' ) ).toBe( 'Basic dXNlcjpwYXNz' );
	} );

	it( 'handles non-ASCII usernames as UTF-8', () => {
		expect( basicAuth( 'zoë', 'x' ) ).toBe(
			`Basic ${ Buffer.from( 'zoë:x', 'utf8' ).toString( 'base64' ) }`
		);
	} );
} );

describe( 'buildSnippets', () => {
	const snippets = buildSnippets( input );
	const byId = ( id: string ) => snippets.find( ( item ) => item.id === id )!;

	it( 'offers every supported client', () => {
		expect( snippets.map( ( item ) => item.id ) ).toEqual( [
			'claude-code',
			'cursor',
			'claude-desktop',
		] );
	} );

	it( 'builds a Claude Code command with the auth header', () => {
		const { code } = byId( 'claude-code' );
		expect( code ).toContain( 'claude mcp add --transport http' );
		expect( code ).toContain( input.mcpUrl );
		expect( code ).toContain(
			`Authorization: ${ basicAuth( input.username, input.password ) }`
		);
	} );

	it( 'builds valid Cursor JSON', () => {
		const parsed = JSON.parse( byId( 'cursor' ).code );
		expect( parsed.mcpServers[ 'mcp-builder' ] ).toEqual( {
			url: input.mcpUrl,
			headers: {
				Authorization: basicAuth( input.username, input.password ),
			},
		} );
	} );

	it( 'builds Claude Desktop JSON using the proxy', () => {
		const server = JSON.parse( byId( 'claude-desktop' ).code ).mcpServers[
			'mcp-builder'
		];
		expect( server.command ).toBe( 'npx' );
		expect( server.args ).toContain(
			'@automattic/mcp-wordpress-remote@latest'
		);
		expect( server.env ).toEqual( {
			WP_API_URL: input.mcpUrl,
			WP_API_USERNAME: input.username,
			WP_API_PASSWORD: input.password,
		} );
	} );

	it( 'keeps the placeholder visible when no password was created', () => {
		const desktop = buildSnippets( {
			...input,
			password: PASSWORD_PLACEHOLDER,
		} ).find( ( item ) => item.id === 'claude-desktop' )!;
		expect( desktop.code ).toContain( PASSWORD_PLACEHOLDER );
	} );
} );
