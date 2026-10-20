<?php
/**
 * One-time migration from the plugin's former name, MCP Builder.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder;

use AmcpBuilder\Safety\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Carries settings, snapshots, the audit log and OAuth data over from
 * MCP Builder (0.4 and earlier), so an existing site keeps its history.
 *
 * Old data is copied, never moved: the old plugin keeps working until it is
 * deactivated, and its own uninstaller removes its data.
 */
final class Migration {

	public const DONE_OPTION = 'amcp_builder_migrated_from_mcp_builder';

	private const OLD_SETTINGS   = 'mcp_builder_settings';
	private const OLD_DB_VERSION = 'mcp_builder_db_version';
	private const OLD_TABLES     = array( 'mcpb_audit', 'mcpb_snapshots', 'mcpb_oauth_clients', 'mcpb_tokens' );
	private const OLD_PLUGIN     = 'mcp-builder/mcp-builder.php';

	/**
	 * Runs once per site. Must run before Schema::maybe_install() so copied
	 * tables are not shadowed by fresh empty ones.
	 */
	public static function maybe_run(): void {
		if ( get_option( self::DONE_OPTION ) ) {
			return;
		}
		$migrated = false;

		$old = get_option( self::OLD_SETTINGS );
		if ( is_array( $old ) && false === get_option( Settings::OPTION ) ) {
			$old['disabled_tools'] = array_map( array( self::class, 'rename_tool' ), (array) ( $old['disabled_tools'] ?? array() ) );
			add_option( Settings::OPTION, $old, '', false );
			$migrated = true;
		}

		if ( self::copy_tables() ) {
			update_option( Schema::VERSION_OPTION, (string) get_option( self::OLD_DB_VERSION, '' ), false );
			$migrated = true;
		}

		update_option( self::DONE_OPTION, $migrated ? gmdate( 'c' ) : 'nothing-to-migrate', false );
	}

	/**
	 * True while the old plugin is still active next to this one.
	 */
	public static function old_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( self::OLD_PLUGIN );
	}

	/**
	 * "mcp-builder/site-info" → "amcp-builder/site-info". Official builder abilities keep their names.
	 */
	public static function rename_tool( $name ): string {
		$name = (string) $name;
		return str_starts_with( $name, 'mcp-builder/' ) ? 'a' . $name : $name;
	}

	/**
	 * Copies each old table that exists into its new name, if the new table does not exist yet.
	 */
	private static function copy_tables(): bool {
		global $wpdb;
		$new_tables = array(
			'mcpb_audit'         => Schema::audit_table(),
			'mcpb_snapshots'     => Schema::snapshot_table(),
			'mcpb_oauth_clients' => Schema::oauth_clients_table(),
			'mcpb_tokens'        => Schema::tokens_table(),
		);

		$copied = false;
		foreach ( self::OLD_TABLES as $old_suffix ) {
			$old = $wpdb->prefix . $old_suffix;
			$new = $new_tables[ $old_suffix ];
			// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time copy of the plugin's own tables.
			if ( $old !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old ) ) ) ) {
				continue;
			}
			if ( $new === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $new ) ) ) ) {
				continue;
			}
			$wpdb->query( $wpdb->prepare( 'CREATE TABLE %i LIKE %i', $new, $old ) );
			$wpdb->query( $wpdb->prepare( 'INSERT INTO %i SELECT * FROM %i', $new, $old ) );
			// phpcs:enable
			$copied = true;
		}

		if ( $copied ) {
			// OAuth tokens are bound to the MCP endpoint URL, which changed with the rename.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Plugin-owned table.
			$wpdb->query( $wpdb->prepare( 'UPDATE %i SET resource = REPLACE(resource, %s, %s)', Schema::tokens_table(), '/mcp-builder/mcp', '/amcp-builder/mcp' ) );
		}
		return $copied;
	}
}
