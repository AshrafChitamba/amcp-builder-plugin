<?php
/**
 * Custom database tables.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Safety;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the audit and snapshot tables.
 */
final class Schema {

	public const VERSION        = '1';
	public const VERSION_OPTION = 'mcp_builder_db_version';

	public static function audit_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mcpb_audit';
	}

	public static function snapshot_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mcpb_snapshots';
	}

	/**
	 * Runs dbDelta when the stored schema version is older than VERSION.
	 */
	public static function maybe_install(): void {
		if ( get_option( self::VERSION_OPTION ) === self::VERSION ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$audit   = self::audit_table();
		$snaps   = self::snapshot_table();

		dbDelta(
			"CREATE TABLE {$audit} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				ability varchar(191) NOT NULL,
				outcome varchar(20) NOT NULL,
				post_id bigint(20) unsigned NOT NULL DEFAULT 0,
				snapshot_id bigint(20) unsigned NOT NULL DEFAULT 0,
				duration_ms int(10) unsigned NOT NULL DEFAULT 0,
				message text NOT NULL,
				input_summary text NOT NULL,
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY ability (ability),
				KEY post_id (post_id)
			) {$charset};
			CREATE TABLE {$snaps} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				ability varchar(191) NOT NULL,
				target_type varchar(20) NOT NULL,
				target_id bigint(20) unsigned NOT NULL DEFAULT 0,
				target_key varchar(191) NOT NULL DEFAULT '',
				label varchar(255) NOT NULL DEFAULT '',
				payload longblob NOT NULL,
				size int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY target (target_type, target_id),
				KEY created_at (created_at)
			) {$charset};"
		);

		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}
