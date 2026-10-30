<?php
/**
 * Audit log storage.
 *
 * @package AcMcp
 */

namespace AcMcp\Safety;

defined( 'ABSPATH' ) || exit;

/**
 * Records who ran which tool, on what, and with which outcome.
 */
class AuditLog {

	public const OUTCOMES = array( 'ok', 'error', 'denied', 'dry-run' );

	private const SECRET_KEYS = '/pass|secret|token|key|auth|signature/i';

	/**
	 * @param array<string, mixed> $entry ability, outcome, post_id, snapshot_id, duration_ms, message, input.
	 */
	public function record( array $entry ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Schema::audit_table(),
			array(
				'created_at'    => current_time( 'mysql', true ),
				'user_id'       => get_current_user_id(),
				'ability'       => substr( (string) $entry['ability'], 0, 191 ),
				'outcome'       => in_array( $entry['outcome'], self::OUTCOMES, true ) ? $entry['outcome'] : 'error',
				'post_id'       => absint( $entry['post_id'] ?? 0 ),
				'snapshot_id'   => absint( $entry['snapshot_id'] ?? 0 ),
				'duration_ms'   => absint( $entry['duration_ms'] ?? 0 ),
				'message'       => mb_substr( (string) ( $entry['message'] ?? '' ), 0, 1000 ),
				'input_summary' => self::summarize( $entry['input'] ?? null ),
			),
			array( '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Paginated, filtered entries, newest first.
	 *
	 * @param array<string, mixed> $filters ability, outcome, post_id, user_id.
	 * @return array{items: list<array<string, mixed>>, total: int}
	 */
	public function query( array $filters, int $page = 1, int $per_page = 50 ): array {
		global $wpdb;
		$table  = Schema::audit_table();
		$where  = array( '1=1' );
		$params = array();

		foreach ( array(
			'ability' => '%s',
			'outcome' => '%s',
			'post_id' => '%d',
			'user_id' => '%d',
		) as $column => $format ) {
			if ( ! empty( $filters[ $column ] ) ) {
				$where[]  = "{$column} = {$format}";
				$params[] = $filters[ $column ];
			}
		}

		$sql_where = implode( ' AND ', $where );
		$offset    = max( 0, ( $page - 1 ) * $per_page );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; $sql_where holds only fixed column names and placeholders, values are prepared.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$sql_where}", array_merge( array( $table ), $params ) ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM %i WHERE {$sql_where} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( array( $table ), $params, array( $per_page, $offset ) ) ),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'items' => array_map( array( $this, 'format' ), $rows ? $rows : array() ),
			'total' => $total,
		);
	}

	public function prune( int $days ): void {
		global $wpdb;
		$table = Schema::audit_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $table, gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) );
	}

	/**
	 * Compact JSON of the input with secrets redacted and long strings cut.
	 *
	 * @param mixed $input Tool input.
	 */
	public static function summarize( $input ): string {
		if ( null === $input || array() === $input ) {
			return '';
		}
		$json = (string) wp_json_encode( self::redact( $input ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return mb_strlen( $json ) > 2000 ? mb_substr( $json, 0, 1999 ) . '…' : $json;
	}

	/**
	 * @param mixed $value Value to clean.
	 * @return mixed
	 */
	private static function redact( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) ) {
			return is_string( $value ) && mb_strlen( $value ) > 200 ? mb_substr( $value, 0, 199 ) . '…' : $value;
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = is_string( $key ) && preg_match( self::SECRET_KEYS, $key ) && ! is_array( $item ) ? '[redacted]' : self::redact( $item );
		}
		return $value;
	}

	/**
	 * @param array<string, string> $row Database row.
	 * @return array<string, mixed>
	 */
	private function format( array $row ): array {
		$user = get_userdata( (int) $row['user_id'] );
		return array(
			'id'           => (int) $row['id'],
			'createdAt'    => gmdate( 'c', (int) strtotime( $row['created_at'] . ' UTC' ) ),
			'user'         => $user ? $user->display_name : '',
			'userId'       => (int) $row['user_id'],
			'ability'      => $row['ability'],
			'outcome'      => $row['outcome'],
			'postId'       => (int) $row['post_id'],
			'snapshotId'   => (int) $row['snapshot_id'],
			'durationMs'   => (int) $row['duration_ms'],
			'message'      => $row['message'],
			'inputSummary' => $row['input_summary'],
		);
	}
}
