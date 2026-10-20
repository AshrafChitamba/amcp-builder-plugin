<?php
/**
 * Snapshot storage and restore.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Safety;

use AmcpBuilder\Bricks\Store;
use AmcpBuilder\Support\Errors;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Copies builder data aside before a write so it can be restored.
 *
 * Post snapshots hold every builder meta key of the post (absent keys are
 * recorded as null, so a restore removes keys the write added). Option
 * snapshots hold one option, e.g. Bricks global classes.
 */
class Snapshots {

	public const TYPE_POST   = 'post';
	public const TYPE_OPTION = 'option';

	/** Post meta that defines a page's builder content. */
	public const POST_META_KEYS = array(
		'_bricks_page_content_2',
		'_bricks_page_header_2',
		'_bricks_page_footer_2',
		'_bricks_page_settings',
		'_bricks_editor_mode',
		'_bricks_template_type',
		'_bricks_template_settings',
		'_elementor_data',
		'_elementor_page_settings',
		'_elementor_edit_mode',
		'_elementor_template_type',
		'_elementor_version',
		'_elementor_conditions',
		'_elementor_code',
		'_elementor_location',
		'_elementor_priority',
		'_wp_page_template',
	);

	/** Options that may be snapshotted. Anything else is refused. */
	public const OPTION_KEYS = array(
		'bricks_global_classes',
		'bricks_global_classes_categories',
		'bricks_global_variables',
		'bricks_global_variables_categories',
		'bricks_theme_styles',
		'bricks_color_palette',
		'bricks_components',
		'bricks_global_queries',
		'bricks_global_settings',
	);

	/**
	 * Snapshots a post's builder meta. Returns the snapshot ID.
	 */
	public function take_post( int $post_id, string $ability, string $label = '' ): int {
		$meta = array();
		foreach ( self::POST_META_KEYS as $key ) {
			$meta[ $key ] = metadata_exists( 'post', $post_id, $key ) ? get_post_meta( $post_id, $key, true ) : null;
		}
		return $this->insert( self::TYPE_POST, $post_id, '', $ability, $label, array( 'meta' => $meta ) );
	}

	/**
	 * Snapshots one allowed option. Returns the snapshot ID, or 0 if not allowed.
	 */
	public function take_option( string $option, string $ability, string $label = '' ): int {
		if ( ! in_array( $option, self::OPTION_KEYS, true ) ) {
			return 0;
		}
		$missing = new \stdClass();
		$value   = get_option( $option, $missing );
		return $this->insert(
			self::TYPE_OPTION,
			0,
			$option,
			$ability,
			$label,
			array(
				'exists' => $value !== $missing,
				'value'  => $value === $missing ? null : $value,
			)
		);
	}

	/**
	 * @return array<string, mixed>|null Snapshot row with decoded payload.
	 */
	public function get( int $id ): ?array {
		global $wpdb;
		$table = Schema::snapshot_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}
		$payload = self::decode( (string) $row['payload'] );
		return null === $payload ? null : $this->format( $row ) + array( 'payload' => $payload );
	}

	/**
	 * Newest snapshots, optionally for one post.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list( int $post_id = 0, int $limit = 20 ): array {
		global $wpdb;
		$table = Schema::snapshot_table();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$rows = $post_id
			? $wpdb->get_results( $wpdb->prepare( 'SELECT id, created_at, user_id, ability, target_type, target_id, target_key, label, size FROM %i WHERE target_type = %s AND target_id = %d ORDER BY id DESC LIMIT %d', $table, self::TYPE_POST, $post_id, $limit ), ARRAY_A )
			: $wpdb->get_results( $wpdb->prepare( 'SELECT id, created_at, user_id, ability, target_type, target_id, target_key, label, size FROM %i ORDER BY id DESC LIMIT %d', $table, $limit ), ARRAY_A );
		// phpcs:enable
		return array_map( array( $this, 'format' ), $rows ? $rows : array() );
	}

	/**
	 * Restores a snapshot, first snapshotting the current state so the
	 * restore itself can be undone.
	 *
	 * @return array{restored: int, undoSnapshotId: int}|WP_Error
	 */
	public function restore( int $id, string $ability ) {
		$snapshot = $this->get( $id );
		if ( ! $snapshot ) {
			return Errors::make( 'snapshot_not_found', __( 'That snapshot does not exist or has expired.', 'amcp-builder' ), __( 'Call list-snapshots to see available snapshots.', 'amcp-builder' ), 404 );
		}

		/* translators: %d: snapshot ID. */
		$label = sprintf( __( 'Before restoring snapshot #%d', 'amcp-builder' ), $id );

		if ( self::TYPE_OPTION === $snapshot['targetType'] ) {
			$undo = $this->take_option( $snapshot['targetKey'], $ability, $label );
			$snapshot['payload']['exists'] ? update_option( $snapshot['targetKey'], $snapshot['payload']['value'] ) : delete_option( $snapshot['targetKey'] );
			return array(
				'restored'       => $id,
				'undoSnapshotId' => $undo,
			);
		}

		$post_id = (int) $snapshot['targetId'];
		if ( ! get_post( $post_id ) ) {
			return Errors::make( 'post_not_found', __( 'The page this snapshot belongs to no longer exists.', 'amcp-builder' ), '', 404 );
		}

		$undo   = $this->take_post( $post_id, $ability, $label );
		$result = $this->restore_post_meta( $post_id, (array) $snapshot['payload']['meta'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'restored'       => $id,
			'undoSnapshotId' => $undo,
		);
	}

	/**
	 * Deletes snapshots older than $days and keeps at most $keep per target.
	 */
	public function prune( int $keep, int $days ): void {
		global $wpdb;
		$table = Schema::snapshot_table();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $table, gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) );

		$targets = $wpdb->get_results( $wpdb->prepare( 'SELECT target_type, target_id, target_key FROM %i GROUP BY target_type, target_id, target_key HAVING COUNT(*) > %d', $table, $keep ), ARRAY_A );
		foreach ( (array) $targets as $target ) {
			$cutoff = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE target_type = %s AND target_id = %d AND target_key = %s ORDER BY id DESC LIMIT 1 OFFSET %d',
					$table,
					$target['target_type'],
					$target['target_id'],
					$target['target_key'],
					$keep - 1
				)
			);
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i WHERE target_type = %s AND target_id = %d AND target_key = %s AND id < %d',
					$table,
					$target['target_type'],
					$target['target_id'],
					$target['target_key'],
					$cutoff
				)
			);
		}
		// phpcs:enable
	}

	/**
	 * @param array<string, mixed> $meta Meta key => value, null meaning absent.
	 * @return true|WP_Error
	 */
	private function restore_post_meta( int $post_id, array $meta ) {
		foreach ( $meta as $key => $value ) {
			if ( ! in_array( $key, self::POST_META_KEYS, true ) ) {
				continue;
			}
			if ( null === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}
			// Bricks element data goes through Bricks' own security checks.
			if ( is_array( $value ) && in_array( $key, Store::AREA_KEYS, true ) && Store::available() ) {
				$saved = Store::write( $post_id, $key, $value );
				if ( is_wp_error( $saved ) ) {
					return $saved;
				}
				continue;
			}
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) && isset( $meta['_elementor_data'] ) ) {
			// VERIFY: Elementor 4.3 Post CSS file class and delete() (core/files/css/post.php).
			\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
			delete_post_meta( $post_id, '_elementor_element_cache' );
		}
		if ( array_key_exists( '_elementor_conditions', $meta ) && class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			// VERIFY: Elementor Pro 4.3 Theme Builder conditions cache (conditions-cache.php regenerate()).
			\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate();
		}
		clean_post_cache( $post_id );
		return true;
	}

	/**
	 * @param array<string, mixed> $payload Snapshot payload.
	 */
	private function insert( string $type, int $target_id, string $target_key, string $ability, string $label, array $payload ): int {
		global $wpdb;
		$blob = self::encode( $payload );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Schema::snapshot_table(),
			array(
				'created_at'  => current_time( 'mysql', true ),
				'user_id'     => get_current_user_id(),
				'ability'     => substr( $ability, 0, 191 ),
				'target_type' => $type,
				'target_id'   => $target_id,
				'target_key'  => $target_key,
				'label'       => mb_substr( $label, 0, 255 ),
				'payload'     => $blob,
				'size'        => strlen( $blob ),
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array<string, mixed> $payload Data to store.
	 */
	public static function encode( array $payload ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Same format WordPress uses for meta; read back with allowed_classes false.
		return (string) gzcompress( serialize( $payload ), 6 );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function decode( string $blob ): ?array {
		$raw = gzuncompress( $blob );
		if ( false === $raw ) {
			return null;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Plugin-written data, objects disallowed.
		$data = unserialize( $raw, array( 'allowed_classes' => false ) );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * @param array<string, string> $row Database row.
	 * @return array<string, mixed>
	 */
	private function format( array $row ): array {
		$user = get_userdata( (int) $row['user_id'] );
		return array(
			'id'         => (int) $row['id'],
			'createdAt'  => gmdate( 'c', (int) strtotime( $row['created_at'] . ' UTC' ) ),
			'user'       => $user ? $user->display_name : '',
			'ability'    => $row['ability'],
			'targetType' => $row['target_type'],
			'targetId'   => (int) $row['target_id'],
			'targetKey'  => $row['target_key'],
			'label'      => $row['label'],
			'size'       => (int) $row['size'],
		);
	}
}
