<?php
/**
 * OAuth 2.1 authorization server for remote MCP clients.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\OAuth;

use AmcpBuilder\Mcp\Server as McpServer;
use AmcpBuilder\Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Lets claude.ai, ChatGPT, Codex and other remote MCP clients connect with
 * "Sign in with WordPress" instead of an Application Password.
 *
 * Implements what the MCP authorization spec asks for: Protected Resource
 * Metadata (RFC 9728), Authorization Server Metadata (RFC 8414, plus the
 * OpenID discovery URL some clients try), Dynamic Client Registration
 * (RFC 7591), Client ID Metadata Documents, authorization code with PKCE
 * (S256 only), refresh token rotation and token revocation (RFC 7009).
 *
 * Endpoints live under /wp-json/amcp-builder/v1/oauth so they work on any
 * host. The /.well-known URLs at the site root are also answered when the
 * web server passes them to WordPress (most do).
 */
class Server {

	public const NAMESPACE = 'amcp-builder/v1';

	/**
	 * Set when a request carried a bearer token that was not accepted.
	 *
	 * @var bool
	 */
	private static bool $rejected_token = false;

	public function __construct(
		private Settings $settings,
		private Clients $clients,
		private Tokens $tokens
	) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_action( 'init', array( $this, 'serve_well_known' ), 1 );
		add_filter( 'determine_current_user', array( $this, 'authenticate' ), 30 );
		add_filter( 'rest_post_dispatch', array( $this, 'challenge' ), 10, 3 );
		add_filter( 'application_password_is_api_request', array( $this, 'skip_app_passwords_on_token_endpoint' ) );
		add_filter( 'rest_allowed_cors_headers', array( $this, 'cors_allowed_headers' ) );
		add_filter( 'rest_exposed_cors_headers', array( $this, 'cors_exposed_headers' ) );
	}

	/**
	 * Canonical form of a resource URL: lowercase scheme and host, no default port, no trailing slash.
	 */
	public static function canonical( string $url ): string {
		$parts = wp_parse_url( trim( $url ) );
		if ( ! $parts || empty( $parts['host'] ) ) {
			return untrailingslashit( $url );
		}
		$scheme = strtolower( $parts['scheme'] ?? 'https' );
		$port   = isset( $parts['port'] ) && ! ( ( 'https' === $scheme && 443 === $parts['port'] ) || ( 'http' === $scheme && 80 === $parts['port'] ) ) ? ':' . $parts['port'] : '';
		$query  = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		return $scheme . '://' . strtolower( $parts['host'] ) . $port . untrailingslashit( $parts['path'] ?? '' ) . $query;
	}

	/**
	 * @param list<string> $headers Allowed request headers.
	 * @return list<string>
	 */
	public function cors_allowed_headers( array $headers ): array {
		return array_values( array_unique( array_merge( $headers, array( 'Authorization', 'Mcp-Session-Id', 'MCP-Protocol-Version', 'Mcp-Method', 'Mcp-Name', 'Last-Event-ID' ) ) ) );
	}

	/**
	 * @param list<string> $headers Exposed response headers.
	 * @return list<string>
	 */
	public function cors_exposed_headers( array $headers ): array {
		return array_values( array_unique( array_merge( $headers, array( 'WWW-Authenticate', 'Mcp-Session-Id' ) ) ) );
	}

	public function enabled(): bool {
		return (bool) $this->settings->get( 'oauth_enabled' ) && ! $this->settings->is_killed();
	}

	public static function issuer(): string {
		return untrailingslashit( rest_url( self::NAMESPACE . '/oauth' ) );
	}

	public static function resource(): string {
		return McpServer::url();
	}

	public static function resource_metadata_url(): string {
		return rest_url( self::NAMESPACE . '/oauth/protected-resource' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function protected_resource_metadata(): array {
		return array(
			'resource'                 => self::resource(),
			'authorization_servers'    => array( self::issuer() ),
			'scopes_supported'         => Tokens::SCOPES,
			'bearer_methods_supported' => array( 'header' ),
			'resource_name'            => get_bloginfo( 'name' ) . ' (AMCP Builder)',
			'resource_documentation'   => 'https://ashrafchitambaa.com',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function authorization_server_metadata(): array {
		$base = self::issuer();
		return array(
			'issuer'                                     => $base,
			'authorization_endpoint'                     => $base . '/authorize',
			'token_endpoint'                             => $base . '/token',
			'registration_endpoint'                      => $base . '/register',
			'revocation_endpoint'                        => $base . '/revoke',
			'scopes_supported'                           => array_merge( Tokens::SCOPES, array( 'offline_access' ) ),
			'response_types_supported'                   => array( 'code' ),
			'response_modes_supported'                   => array( 'query' ),
			'grant_types_supported'                      => array( 'authorization_code', 'refresh_token' ),
			'token_endpoint_auth_methods_supported'      => array( 'none', 'client_secret_post', 'client_secret_basic' ),
			'revocation_endpoint_auth_methods_supported' => array( 'none', 'client_secret_post', 'client_secret_basic' ),
			'code_challenge_methods_supported'           => array( 'S256' ),
			'client_id_metadata_document_supported'      => true,
			'authorization_response_iss_parameter_supported' => true,
		);
	}

	public function routes(): void {
		$open = array( $this, 'is_enabled_for_request' );

		register_rest_route(
			self::NAMESPACE,
			'/oauth/protected-resource',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => fn() => $this->json( self::protected_resource_metadata() ),
				'permission_callback' => $open,
			)
		);
		foreach ( array( '/oauth/.well-known/oauth-authorization-server', '/oauth/.well-known/openid-configuration', '/oauth/metadata' ) as $route ) {
			register_rest_route(
				self::NAMESPACE,
				$route,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn() => $this->json( self::authorization_server_metadata() ),
					'permission_callback' => $open,
				)
			);
		}
		register_rest_route(
			self::NAMESPACE,
			'/oauth/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register_client' ),
				'permission_callback' => $open,
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/oauth/authorize',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'authorize' ),
				'permission_callback' => $open,
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/oauth/token',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'token' ),
				'permission_callback' => $open,
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/oauth/revoke',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'revoke' ),
				'permission_callback' => $open,
			)
		);
	}

	/**
	 * Public OAuth endpoints are open to anyone (that is how OAuth works), but
	 * only while OAuth is enabled. Each endpoint does its own validation.
	 */
	public function is_enabled_for_request(): bool {
		return $this->enabled();
	}

	/**
	 * Answers the root /.well-known discovery URLs when WordPress receives them.
	 */
	public function serve_well_known(): void {
		if ( ! $this->enabled() || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		if ( ! str_contains( $path, '/.well-known/' ) ) {
			return;
		}

		$resource_path = (string) wp_parse_url( self::resource(), PHP_URL_PATH );
		$issuer_path   = (string) wp_parse_url( self::issuer(), PHP_URL_PATH );

		$documents = array(
			'/.well-known/oauth-protected-resource' . $resource_path => 'resource',
			'/.well-known/oauth-protected-resource'   => 'resource',
			'/.well-known/oauth-authorization-server' . $issuer_path => 'server',
			'/.well-known/openid-configuration' . $issuer_path => 'server',
			'/.well-known/oauth-authorization-server' => 'server',
			'/.well-known/openid-configuration'       => 'server',
		);
		$kind      = $documents[ untrailingslashit( $path ) ] ?? null;
		if ( null === $kind ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Access-Control-Allow-Origin: *' );
		echo wp_json_encode( 'resource' === $kind ? self::protected_resource_metadata() : self::authorization_server_metadata(), JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Accepts "Authorization: Bearer" tokens, but only on AMCP Builder's own routes.
	 *
	 * @param int|false $user_id User found so far.
	 * @return int|false
	 */
	public function authenticate( $user_id ) {
		if ( $user_id || ! $this->enabled() || ! self::is_own_rest_request() ) {
			return $user_id;
		}
		$header = self::authorization_header();
		if ( ! preg_match( '/^Bearer\s+(\S+)$/i', $header, $match ) ) {
			return $user_id;
		}

		$row  = $this->tokens->find( $match[1], array( 'access', 'pat' ) );
		$user = $row ? get_userdata( (int) $row['user_id'] ) : false;
		if ( ! $row || ! $user || ( '' !== $row['resource'] && self::canonical( $row['resource'] ) !== self::canonical( self::resource() ) ) ) {
			self::$rejected_token = true;
			return $user_id;
		}

		Tokens::set_current( $row );
		$this->tokens->touch( (int) $row['id'] );
		return (int) $user->ID;
	}

	/**
	 * Tells clients where to find the authorization server when they are not signed in.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param WP_REST_Server   $server   Server.
	 * @param WP_REST_Request  $request  Request.
	 */
	public function challenge( $response, $server, $request ) {
		if ( $this->enabled() && $response instanceof WP_REST_Response && 401 === $response->get_status()
			&& str_starts_with( $request->get_route(), '/' . McpServer::NAMESPACE . '/' . McpServer::ROUTE ) ) {
			$error = self::$rejected_token ? ', error="invalid_token", error_description="The access token is invalid or expired"' : '';
			$response->header( 'WWW-Authenticate', sprintf( 'Bearer resource_metadata="%s", scope="%s"%s', self::resource_metadata_url(), Tokens::SCOPE_FULL, $error ) );
		}
		return $response;
	}

	/**
	 * Basic auth on the token endpoint is a client secret, not an Application Password.
	 */
	public function skip_app_passwords_on_token_endpoint( bool $is_api ): bool {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared.
		return ( str_contains( $uri, 'amcp-builder/v1/oauth/token' ) || str_contains( $uri, 'amcp-builder/v1/oauth/revoke' ) ) ? false : $is_api;
	}

	public function register_client( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		$body = is_array( $body ) ? $body : $request->get_body_params();
		$res  = $this->clients->register( is_array( $body ) ? $body : array() );
		if ( is_wp_error( $res ) ) {
			return $this->oauth_error( 'invalid_client_metadata', $res->get_error_message() );
		}
		return $this->json( $res, 201 );
	}

	/**
	 * Authorization endpoint: shows the consent screen and issues codes.
	 */
	public function authorize( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_params();
		$client = $this->clients->get( (string) ( $params['client_id'] ?? '' ) );
		$uri    = (string) ( $params['redirect_uri'] ?? '' );

		// Never redirect to an unverified URI: show the error instead.
		if ( ! $client ) {
			Consent::error_page( __( 'This app is not registered with this site. Try connecting again from the app.', 'amcp-builder' ) );
		}
		if ( '' === $uri && 1 === count( $client['redirect_uris'] ) ) {
			$uri = $client['redirect_uris'][0];
		}
		if ( ! Clients::redirect_matches( $uri, $client['redirect_uris'] ) ) {
			Consent::error_page( __( 'The app sent an unexpected return address, so the request was stopped.', 'amcp-builder' ) );
		}

		$state = isset( $params['state'] ) ? (string) $params['state'] : null;
		if ( 'code' !== ( $params['response_type'] ?? '' ) ) {
			$this->redirect_error( $uri, 'unsupported_response_type', $state );
		}
		if ( 'S256' !== ( $params['code_challenge_method'] ?? '' ) || ! preg_match( '/^[A-Za-z0-9_-]{43,128}$/', (string) ( $params['code_challenge'] ?? '' ) ) ) {
			$this->redirect_error( $uri, 'invalid_request', $state, 'PKCE with S256 is required.' );
		}
		$resource = (string) ( $params['resource'] ?? '' );
		if ( '' !== $resource && self::canonical( $resource ) !== self::canonical( self::resource() ) ) {
			$this->redirect_error( $uri, 'invalid_target', $state );
		}

		// The REST API ignores login cookies without a nonce, so check the cookie directly.
		$user_id = (int) wp_validate_auth_cookie( '', 'logged_in' );
		if ( ! $user_id ) {
			wp_safe_redirect( wp_login_url( add_query_arg( $request->get_query_params(), self::issuer() . '/authorize' ) ) );
			exit;
		}
		wp_set_current_user( $user_id );
		if ( ! current_user_can( 'edit_posts' ) ) {
			Consent::error_page( __( 'Your account is not allowed to connect AI assistants to this site.', 'amcp-builder' ) );
		}

		$requested_scope = Tokens::normalize_scope( (string) ( $params['scope'] ?? '' ) );

		if ( 'POST' !== $request->get_method() ) {
			Consent::page( $client, $uri, $requested_scope, $request->get_query_params() );
		}

		if ( ! wp_verify_nonce( (string) ( $params['_mcpb_nonce'] ?? '' ), Consent::NONCE . $client['client_id'] ) ) {
			Consent::error_page( __( 'The approval form expired. Go back to the app and connect again.', 'amcp-builder' ) );
		}
		if ( 'approve' !== ( $params['decision'] ?? '' ) ) {
			$this->redirect_error( $uri, 'access_denied', $state );
		}

		$scope = 'read' === ( $params['access'] ?? '' ) ? Tokens::SCOPE_READ : Tokens::SCOPE_FULL;
		$code  = $this->tokens->issue(
			'code',
			array(
				'client_id'      => $client['client_id'],
				'user_id'        => $user_id,
				'scope'          => $scope,
				'redirect_uri'   => $uri,
				'code_challenge' => (string) $params['code_challenge'],
				'resource'       => $resource,
			),
			Tokens::CODE_TTL
		);

		$this->redirect(
			$uri,
			array_filter(
				array(
					'code'  => $code,
					'state' => $state,
					'iss'   => self::issuer(),
				),
				static fn( $value ): bool => null !== $value
			)
		);
	}

	/**
	 * Token endpoint: authorization_code and refresh_token grants.
	 */
	public function token( WP_REST_Request $request ): WP_REST_Response {
		$params                 = $request->get_body_params() ? $request->get_body_params() : $request->get_params();
		[ $client_id, $secret ] = self::client_credentials( $params );
		$client                 = $this->clients->get( $client_id );
		if ( ! $client || ! $this->clients->authenticate( $client, $secret ) ) {
			return $this->oauth_error( 'invalid_client', 'Unknown client or wrong client secret.', 401 );
		}

		$grant = (string) ( $params['grant_type'] ?? '' );
		if ( 'authorization_code' === $grant ) {
			$row = $this->tokens->find( (string) ( $params['code'] ?? '' ), array( 'code' ) );
			if ( ! $row || $row['client_id'] !== $client['client_id'] ) {
				return $this->oauth_error( 'invalid_grant', 'The authorization code is invalid or expired.' );
			}
			// Codes are single use: revoke before any further check.
			$this->tokens->revoke_id( (int) $row['id'] );
			if ( isset( $params['redirect_uri'] ) && (string) $params['redirect_uri'] !== (string) $row['redirect_uri'] ) {
				return $this->oauth_error( 'invalid_grant', 'redirect_uri does not match the authorization request.' );
			}
			$verifier = (string) ( $params['code_verifier'] ?? '' );
			if ( ! hash_equals( (string) $row['code_challenge'], self::s256( $verifier ) ) ) {
				return $this->oauth_error( 'invalid_grant', 'PKCE verification failed.' );
			}
		} elseif ( 'refresh_token' === $grant ) {
			$row = $this->tokens->find( (string) ( $params['refresh_token'] ?? '' ), array( 'refresh' ) );
			if ( ! $row || $row['client_id'] !== $client['client_id'] ) {
				return $this->oauth_error( 'invalid_grant', 'The refresh token is invalid or expired.' );
			}
			// Rotation: each refresh token works once.
			$this->tokens->revoke_id( (int) $row['id'] );
		} else {
			return $this->oauth_error( 'unsupported_grant_type', 'Use authorization_code or refresh_token.' );
		}

		if ( isset( $params['resource'] ) && self::canonical( (string) $params['resource'] ) !== self::canonical( self::resource() ) ) {
			return $this->oauth_error( 'invalid_target', 'The resource does not match this MCP server.' );
		}

		$user = get_userdata( (int) $row['user_id'] );
		if ( ! $user || ! user_can( $user, 'edit_posts' ) ) {
			return $this->oauth_error( 'invalid_grant', 'The user can no longer use this connection.' );
		}

		$data = array(
			'client_id' => $client['client_id'],
			'user_id'   => (int) $row['user_id'],
			'scope'     => $row['scope'],
			'resource'  => $row['resource'],
		);
		$this->clients->touch( $client['client_id'] );

		return $this->json(
			array(
				'access_token'  => $this->tokens->issue( 'access', $data, Tokens::ACCESS_TTL ),
				'token_type'    => 'Bearer',
				'expires_in'    => Tokens::ACCESS_TTL,
				'refresh_token' => $this->tokens->issue( 'refresh', $data, Tokens::REFRESH_TTL ),
				'scope'         => $row['scope'],
			)
		);
	}

	/**
	 * Token revocation (RFC 7009). Always answers 200.
	 */
	public function revoke( WP_REST_Request $request ): WP_REST_Response {
		$params                 = $request->get_body_params() ? $request->get_body_params() : $request->get_params();
		[ $client_id, $secret ] = self::client_credentials( $params );
		$client                 = $this->clients->get( $client_id );
		if ( $client && $this->clients->authenticate( $client, $secret ) ) {
			$row = $this->tokens->find( (string) ( $params['token'] ?? '' ), array( 'access', 'refresh' ) );
			if ( $row && $row['client_id'] === $client['client_id'] ) {
				$this->tokens->revoke_client( (int) $row['user_id'], $client['client_id'] );
			}
		}
		return $this->json( array() );
	}

	/**
	 * The client_id and secret from the body or an HTTP Basic header.
	 *
	 * @param array<string, mixed> $params Request body.
	 * @return array{0: string, 1: string|null}
	 */
	private static function client_credentials( array $params ): array {
		$header = self::authorization_header();
		if ( preg_match( '/^Basic\s+(\S+)$/i', $header, $match ) ) {
			$decoded = base64_decode( $match[1], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- HTTP Basic client authentication.
			if ( false !== $decoded && str_contains( $decoded, ':' ) ) {
				[ $id, $secret ] = explode( ':', $decoded, 2 );
				return array( rawurldecode( $id ), rawurldecode( $secret ) );
			}
		}
		return array( (string) ( $params['client_id'] ?? '' ), isset( $params['client_secret'] ) ? (string) $params['client_secret'] : null );
	}

	public static function s256( string $verifier ): string {
		return rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE S256 encoding.
	}

	/**
	 * The Authorization header, including servers that move it to REDIRECT_HTTP_AUTHORIZATION.
	 */
	public static function authorization_header(): string {
		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return trim( (string) wp_unslash( $_SERVER[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parsed as a token, never output.
			}
		}
		return '';
	}

	/**
	 * Whether the current request targets one of AMCP Builder's REST namespaces.
	 */
	private static function is_own_rest_request(): bool {
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared.
		$prefix = '/' . rest_get_url_prefix() . '/' . McpServer::NAMESPACE . '/';
		return str_contains( $uri, $prefix ) || str_contains( rawurldecode( $uri ), 'rest_route=/' . McpServer::NAMESPACE . '/' );
	}

	/**
	 * @param array<string, mixed> $data Response body.
	 */
	private function json( array $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( (object) $data, $status );
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	private function oauth_error( string $error, string $description, int $status = 400 ): WP_REST_Response {
		return $this->json(
			array(
				'error'             => $error,
				'error_description' => $description,
			),
			$status
		);
	}

	private function redirect_error( string $uri, string $error, ?string $state, string $description = '' ): void {
		$this->redirect(
			$uri,
			array_filter(
				array(
					'error'             => $error,
					'error_description' => $description ? $description : null,
					'state'             => $state,
					'iss'               => self::issuer(),
				),
				static fn( $value ): bool => null !== $value
			)
		);
	}

	/**
	 * Redirects to a client's verified redirect URI (an external host, so wp_redirect, not wp_safe_redirect).
	 *
	 * @param array<string, string> $params Query parameters to add.
	 */
	private function redirect( string $uri, array $params ): void {
		$separator = str_contains( $uri, '?' ) ? '&' : '?';
		wp_redirect( $uri . $separator . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Redirect URI was verified against the registered client.
		exit;
	}
}
