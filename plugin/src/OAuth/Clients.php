<?php
/**
 * OAuth client registry.
 *
 * @package AcMcp
 */

namespace AcMcp\OAuth;

use AcMcp\Safety\Schema;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Knows which apps (claude.ai, ChatGPT, Codex, ...) may ask users for access.
 *
 * Two ways to become a client, both from the MCP authorization spec:
 * - Dynamic Client Registration (RFC 7591): the app POSTs its metadata.
 * - Client ID Metadata Documents: the client_id is an https URL serving the
 *   app's metadata, fetched and validated on first use.
 *
 * Registering grants nothing by itself: a signed-in WordPress user still has
 * to approve each connection on the consent screen.
 */
class Clients {

	private const CIMD_CACHE = 'acmcp_cimd_';

	/**
	 * @return array<string, mixed>|null Client row with decoded redirect_uris.
	 */
	public function get( string $client_id ): ?array {
		global $wpdb;
		$table = Schema::oauth_clients_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE client_id = %s', $table, $client_id ), ARRAY_A );
		if ( $row ) {
			$row['redirect_uris'] = (array) json_decode( (string) $row['redirect_uris'], true );
			return $row;
		}
		return self::is_metadata_url( $client_id ) ? $this->from_metadata_document( $client_id ) : null;
	}

	/**
	 * Dynamic Client Registration.
	 *
	 * @param array<string, mixed> $metadata Client metadata from the request.
	 * @return array<string, mixed>|WP_Error Registration response.
	 */
	public function register( array $metadata ) {
		$redirects = self::valid_redirects( $metadata['redirect_uris'] ?? null );
		if ( is_wp_error( $redirects ) ) {
			return $redirects;
		}

		$method = (string) ( $metadata['token_endpoint_auth_method'] ?? 'none' );
		if ( ! in_array( $method, array( 'none', 'client_secret_post', 'client_secret_basic' ), true ) ) {
			$method = 'none';
		}

		$client_id = 'acmcp_' . bin2hex( random_bytes( 16 ) );
		$secret    = 'none' === $method ? '' : bin2hex( random_bytes( 32 ) );
		$name      = mb_substr( sanitize_text_field( (string) ( $metadata['client_name'] ?? __( 'Unnamed app', 'ac-mcp-for-bricks-elementor' ) ) ), 0, 191 );

		$this->store( $client_id, $name, $redirects, 'dcr', $secret );

		$response = array(
			'client_id'                  => $client_id,
			'client_id_issued_at'        => time(),
			'client_name'                => $name,
			'redirect_uris'              => $redirects,
			'grant_types'                => array( 'authorization_code', 'refresh_token' ),
			'response_types'             => array( 'code' ),
			'token_endpoint_auth_method' => $method,
		);
		if ( '' !== $secret ) {
			$response['client_secret']            = $secret;
			$response['client_secret_expires_at'] = 0;
		}
		return $response;
	}

	/**
	 * Checks a client secret when the client registered with one.
	 */
	public function authenticate( array $client, ?string $secret ): bool {
		if ( '' === (string) $client['secret_hash'] ) {
			return true;
		}
		return null !== $secret && hash_equals( (string) $client['secret_hash'], Tokens::hash( $secret ) );
	}

	/**
	 * Removes self-registered clients that were never used within a week.
	 * claude.ai registers a new client for every connection attempt.
	 */
	public function prune(): void {
		global $wpdb;
		$table = Schema::oauth_clients_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE last_used_at IS NULL AND created_at < %s', $table, gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) ) );
	}

	public function touch( string $client_id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
		$wpdb->update( Schema::oauth_clients_table(), array( 'last_used_at' => current_time( 'mysql', true ) ), array( 'client_id' => $client_id ) );
	}

	/**
	 * Redirect URIs must be https, or http on a loopback address for desktop and command-line apps.
	 *
	 * @param mixed $uris Requested redirect URIs.
	 * @return list<string>|WP_Error
	 */
	public static function valid_redirects( $uris ) {
		if ( ! is_array( $uris ) || ! $uris || count( $uris ) > 10 ) {
			return new WP_Error( 'invalid_redirect_uri', 'redirect_uris must list 1 to 10 URIs.' );
		}
		$clean = array();
		foreach ( $uris as $uri ) {
			if ( ! is_string( $uri ) || ! self::allowed_redirect( $uri ) ) {
				return new WP_Error( 'invalid_redirect_uri', 'Redirect URIs must use https, or http on localhost.' );
			}
			$clean[] = $uri;
		}
		return $clean;
	}

	public static function allowed_redirect( string $uri ): bool {
		$parts = wp_parse_url( $uri );
		if ( ! $parts || empty( $parts['scheme'] ) || empty( $parts['host'] ) || isset( $parts['fragment'] ) ) {
			return false;
		}
		if ( 'https' === $parts['scheme'] ) {
			return true;
		}
		return 'http' === $parts['scheme'] && in_array( $parts['host'], array( 'localhost', '127.0.0.1', '[::1]', '::1' ), true );
	}

	/**
	 * Exact match, except loopback redirects may use any port (RFC 8252 section 7.3).
	 *
	 * @param list<string> $registered Registered URIs.
	 */
	public static function redirect_matches( string $uri, array $registered ): bool {
		if ( in_array( $uri, $registered, true ) ) {
			return true;
		}
		$given = wp_parse_url( $uri );
		foreach ( $registered as $candidate ) {
			$known = wp_parse_url( $candidate );
			if ( $given && $known && 'http' === ( $given['scheme'] ?? '' ) && ( $known['host'] ?? '' ) === ( $given['host'] ?? '' )
				&& in_array( $given['host'] ?? '', array( 'localhost', '127.0.0.1', '[::1]', '::1' ), true )
				&& ( $known['path'] ?? '' ) === ( $given['path'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}

	public static function is_metadata_url( string $client_id ): bool {
		$parts = wp_parse_url( $client_id );
		return $parts && 'https' === ( $parts['scheme'] ?? '' ) && ! empty( $parts['host'] ) && ! empty( $parts['path'] ) && '/' !== $parts['path'];
	}

	/**
	 * Fetches and validates a Client ID Metadata Document.
	 *
	 * @return array<string, mixed>|null
	 */
	private function from_metadata_document( string $url ): ?array {
		$cache_key = self::CIMD_CACHE . md5( $url );
		$document  = get_transient( $cache_key );

		if ( ! is_array( $document ) ) {
			// wp_safe_remote_get() refuses private and local addresses (SSRF protection).
			$response = wp_safe_remote_get(
				$url,
				array(
					'timeout'             => 5,
					'redirection'         => 0,
					'limit_response_size' => 65536,
					'headers'             => array( 'Accept' => 'application/json' ),
				)
			);
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return null;
			}
			$document = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $document ) || ( $document['client_id'] ?? '' ) !== $url ) {
				return null;
			}
			set_transient( $cache_key, $document, DAY_IN_SECONDS );
		}

		$redirects = self::valid_redirects( $document['redirect_uris'] ?? null );
		if ( is_wp_error( $redirects ) ) {
			return null;
		}
		$name = mb_substr( sanitize_text_field( (string) ( $document['client_name'] ?? wp_parse_url( $url, PHP_URL_HOST ) ) ), 0, 191 );
		$this->store( $url, $name, $redirects, 'cimd', '' );

		return array(
			'client_id'     => $url,
			'name'          => $name,
			'redirect_uris' => $redirects,
			'secret_hash'   => '',
			'source'        => 'cimd',
		);
	}

	/**
	 * @param list<string> $redirects Redirect URIs.
	 */
	private function store( string $client_id, string $name, array $redirects, string $source, string $secret ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
		$wpdb->replace(
			Schema::oauth_clients_table(),
			array(
				'client_id'     => $client_id,
				'name'          => $name,
				'redirect_uris' => (string) wp_json_encode( array_values( $redirects ) ),
				'secret_hash'   => '' === $secret ? '' : Tokens::hash( $secret ),
				'source'        => $source,
				'created_at'    => current_time( 'mysql', true ),
			)
		);
	}
}
