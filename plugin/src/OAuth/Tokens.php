<?php
/**
 * Token storage for OAuth and personal access tokens.
 *
 * @package McpBuilder
 */

namespace McpBuilder\OAuth;

use McpBuilder\Safety\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Issues and checks bearer tokens. Only SHA-256 hashes are stored, so a
 * database leak does not expose usable tokens.
 *
 * Kinds: "code" (authorization code, 10 minutes), "access" (1 hour),
 * "refresh" (30 days, rotated on use) and "pat" (personal access token
 * created by a user for clients that only support a static bearer token).
 */
class Tokens {

	public const SCOPE_FULL = 'mcp';
	public const SCOPE_READ = 'mcp:read';
	public const SCOPES     = array( self::SCOPE_FULL, self::SCOPE_READ );

	public const CODE_TTL    = 600;
	public const ACCESS_TTL  = HOUR_IN_SECONDS;
	public const REFRESH_TTL = 30 * DAY_IN_SECONDS;

	/**
	 * Token row used for the current request, set by the authenticator.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $current = null;

	public static function hash( string $token ): string {
		return hash( 'sha256', $token );
	}

	/**
	 * Creates a token and returns its plain value (shown once, never stored).
	 *
	 * @param array<string, mixed> $data client_id, user_id, scope, label, redirect_uri, code_challenge, resource.
	 */
	public function issue( string $kind, array $data, ?int $ttl ): string {
		global $wpdb;
		$prefix = array(
			'code'    => 'mcpbc_',
			'access'  => 'mcpba_',
			'refresh' => 'mcpbr_',
			'pat'     => 'mcpbp_',
		)[ $kind ];
		$token  = $prefix . bin2hex( random_bytes( 32 ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Schema::tokens_table(),
			array(
				'token_hash'     => self::hash( $token ),
				'kind'           => $kind,
				'client_id'      => (string) ( $data['client_id'] ?? '' ),
				'user_id'        => (int) $data['user_id'],
				'scope'          => self::normalize_scope( (string) ( $data['scope'] ?? '' ) ),
				'label'          => mb_substr( (string) ( $data['label'] ?? '' ), 0, 191 ),
				'redirect_uri'   => (string) ( $data['redirect_uri'] ?? '' ),
				'code_challenge' => (string) ( $data['code_challenge'] ?? '' ),
				'resource'       => (string) ( $data['resource'] ?? '' ),
				'created_at'     => current_time( 'mysql', true ),
				'expires_at'     => null === $ttl ? null : gmdate( 'Y-m-d H:i:s', time() + $ttl ),
			)
		);
		return $token;
	}

	/**
	 * A valid (unexpired, unrevoked) token row of the given kinds, or null.
	 *
	 * @param list<string> $kinds Accepted kinds.
	 * @return array<string, mixed>|null
	 */
	public function find( string $token, array $kinds ): ?array {
		global $wpdb;
		$table = Schema::tokens_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE token_hash = %s AND revoked = 0', $table, self::hash( $token ) ), ARRAY_A );
		if ( ! $row || ! in_array( $row['kind'], $kinds, true ) ) {
			return null;
		}
		if ( $row['expires_at'] && strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
			return null;
		}
		return $row;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_row( int $id ): ?array {
		global $wpdb;
		$table = Schema::tokens_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A );
		return $row ? $row : null;
	}

	public function revoke_id( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
		$wpdb->update( Schema::tokens_table(), array( 'revoked' => 1 ), array( 'id' => $id ) );
	}

	/**
	 * Revokes every token a user granted to one client (disconnects the app).
	 */
	public function revoke_client( int $user_id, string $client_id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
		$wpdb->update(
			Schema::tokens_table(),
			array( 'revoked' => 1 ),
			array(
				'user_id'   => $user_id,
				'client_id' => $client_id,
			)
		);
	}

	public function touch( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
		$wpdb->update( Schema::tokens_table(), array( 'last_used_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
	}

	/**
	 * Active connections and personal tokens of a user, newest first. Admins may pass 0 for all users.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list_active( int $user_id ): array {
		global $wpdb;
		$table   = Schema::tokens_table();
		$clients = Schema::oauth_clients_table();
		$where   = $user_id ? 't.user_id = %d AND' : '';
		$params  = $user_id ? array( $table, $clients, $user_id ) : array( $table, $clients );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned tables; $where only adds a placeholder whose value is in $params.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.id, t.kind, t.client_id, t.user_id, t.scope, t.label, t.created_at, t.expires_at, t.last_used_at, c.name AS client_name
				FROM %i t LEFT JOIN %i c ON c.client_id = t.client_id
				WHERE {$where} t.revoked = 0 AND t.kind IN ('refresh','pat') AND (t.expires_at IS NULL OR t.expires_at > UTC_TIMESTAMP())
				ORDER BY t.id DESC LIMIT 200",
				$params
			),
			ARRAY_A
		);
		// phpcs:enable
		return array_map(
			static function ( array $row ): array {
				$user = get_userdata( (int) $row['user_id'] );
				return array(
					'id'         => (int) $row['id'],
					'kind'       => 'pat' === $row['kind'] ? 'token' : 'app',
					'name'       => 'pat' === $row['kind'] ? $row['label'] : ( $row['client_name'] ? $row['client_name'] : $row['client_id'] ),
					'clientId'   => $row['client_id'],
					'user'       => $user ? $user->display_name : '',
					'userId'     => (int) $row['user_id'],
					'readOnly'   => self::SCOPE_READ === $row['scope'],
					'createdAt'  => gmdate( 'c', (int) strtotime( $row['created_at'] . ' UTC' ) ),
					'expiresAt'  => $row['expires_at'] ? gmdate( 'c', (int) strtotime( $row['expires_at'] . ' UTC' ) ) : null,
					'lastUsedAt' => $row['last_used_at'] ? gmdate( 'c', (int) strtotime( $row['last_used_at'] . ' UTC' ) ) : null,
				);
			},
			$rows ? $rows : array()
		);
	}

	/**
	 * Deletes expired and revoked rows.
	 */
	public function prune(): void {
		global $wpdb;
		$table = Schema::tokens_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE revoked = 1 OR (expires_at IS NOT NULL AND expires_at < UTC_TIMESTAMP())', $table ) );
	}

	/**
	 * The scope string if valid, else full access.
	 */
	public static function normalize_scope( string $scope ): string {
		$requested = preg_split( '/\s+/', trim( $scope ) );
		return in_array( self::SCOPE_READ, (array) $requested, true ) && ! in_array( self::SCOPE_FULL, (array) $requested, true )
			? self::SCOPE_READ
			: self::SCOPE_FULL;
	}

	/**
	 * @param array<string, mixed>|null $row Token used for this request.
	 */
	public static function set_current( ?array $row ): void {
		self::$current = $row;
	}

	/** True when this request is authenticated with a read-only token. */
	public static function current_is_read_only(): bool {
		return null !== self::$current && self::SCOPE_READ === self::$current['scope'];
	}
}
