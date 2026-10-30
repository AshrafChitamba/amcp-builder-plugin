<?php
/**
 * @package AcMcp
 */

namespace AcMcp\Tests\Unit;

use Brain\Monkey\Functions;
use AcMcp\OAuth\Clients;
use AcMcp\OAuth\Server;
use AcMcp\OAuth\Tokens;
use AcMcp\Tests\TestCase;

final class OAuthTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'untrailingslashit' )->alias( static fn( $value ) => rtrim( (string) $value, '/\\' ) );
	}

	public function test_pkce_s256_matches_rfc7636_example(): void {
		$this->assertSame( 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', Server::s256( 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk' ) );
	}

	public function test_canonical_resource_ignores_case_default_port_and_trailing_slash(): void {
		$this->assertSame(
			Server::canonical( 'https://example.com/wp-json/ac-mcp/mcp' ),
			Server::canonical( 'HTTPS://Example.COM:443/wp-json/ac-mcp/mcp/' )
		);
		$this->assertNotSame( Server::canonical( 'https://example.com/wp-json/ac-mcp/mcp' ), Server::canonical( 'https://evil.test/wp-json/ac-mcp/mcp' ) );
		$this->assertSame( 'http://localhost:10003/wp-json/ac-mcp/mcp', Server::canonical( 'http://localhost:10003/wp-json/ac-mcp/mcp' ) );
	}

	public function test_redirect_uris_must_be_https_or_loopback(): void {
		$this->assertTrue( Clients::allowed_redirect( 'https://claude.ai/api/mcp/auth_callback' ) );
		$this->assertTrue( Clients::allowed_redirect( 'https://chatgpt.com/connector_platform_oauth_redirect' ) );
		$this->assertTrue( Clients::allowed_redirect( 'http://127.0.0.1:4545/callback' ) );
		$this->assertTrue( Clients::allowed_redirect( 'http://localhost/callback' ) );
		$this->assertFalse( Clients::allowed_redirect( 'http://evil.test/callback' ) );
		$this->assertFalse( Clients::allowed_redirect( 'javascript:alert(1)' ) );
		$this->assertFalse( Clients::allowed_redirect( 'https://claude.ai/cb#frag' ) );
	}

	public function test_loopback_redirects_match_any_port_but_same_path(): void {
		$registered = array( 'http://localhost/callback', 'http://127.0.0.1/callback' );
		$this->assertTrue( Clients::redirect_matches( 'http://127.0.0.1:53111/callback', $registered ) );
		$this->assertTrue( Clients::redirect_matches( 'http://localhost:8080/callback', $registered ) );
		$this->assertFalse( Clients::redirect_matches( 'http://127.0.0.1:53111/other', $registered ) );
		$this->assertFalse( Clients::redirect_matches( 'https://claude.ai/api/mcp/auth_callback', $registered ) );
		$this->assertTrue( Clients::redirect_matches( 'https://claude.ai/api/mcp/auth_callback', array( 'https://claude.ai/api/mcp/auth_callback' ) ) );
		$this->assertFalse( Clients::redirect_matches( 'https://claude.ai/api/mcp/auth_callback/x', array( 'https://claude.ai/api/mcp/auth_callback' ) ) );
	}

	public function test_valid_redirects_rejects_empty_or_bad_lists(): void {
		Functions\when( '__' )->returnArg();
		$this->assertInstanceOf( \WP_Error::class, Clients::valid_redirects( array() ) );
		$this->assertInstanceOf( \WP_Error::class, Clients::valid_redirects( 'https://a.test/cb' ) );
		$this->assertInstanceOf( \WP_Error::class, Clients::valid_redirects( array( 'https://a.test/cb', 'http://b.test/cb' ) ) );
		$this->assertSame( array( 'https://a.test/cb' ), Clients::valid_redirects( array( 'https://a.test/cb' ) ) );
	}

	public function test_metadata_document_client_ids_need_https_and_a_path(): void {
		$this->assertTrue( Clients::is_metadata_url( 'https://claude.ai/oauth/claude-code-client-metadata' ) );
		$this->assertTrue( Clients::is_metadata_url( 'https://chatgpt.com/oauth/client.json' ) );
		$this->assertFalse( Clients::is_metadata_url( 'acmcp_0123abcd' ) );
		$this->assertFalse( Clients::is_metadata_url( 'http://chatgpt.com/oauth/client.json' ) );
		$this->assertFalse( Clients::is_metadata_url( 'https://chatgpt.com/' ) );
	}

	public function test_scope_defaults_to_full_and_read_only_only_when_alone(): void {
		$this->assertSame( Tokens::SCOPE_FULL, Tokens::normalize_scope( '' ) );
		$this->assertSame( Tokens::SCOPE_FULL, Tokens::normalize_scope( 'mcp offline_access' ) );
		$this->assertSame( Tokens::SCOPE_READ, Tokens::normalize_scope( 'mcp:read offline_access' ) );
		$this->assertSame( Tokens::SCOPE_FULL, Tokens::normalize_scope( 'mcp mcp:read' ) );
	}

	public function test_read_only_token_flag(): void {
		Tokens::set_current( array( 'scope' => Tokens::SCOPE_READ ) );
		$this->assertTrue( Tokens::current_is_read_only() );
		Tokens::set_current( array( 'scope' => Tokens::SCOPE_FULL ) );
		$this->assertFalse( Tokens::current_is_read_only() );
		Tokens::set_current( null );
		$this->assertFalse( Tokens::current_is_read_only() );
	}
}
