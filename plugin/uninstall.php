<?php
/**
 * Removes AC MCP's settings, tables and scheduled job when the plugin is deleted.
 *
 * OAuth connections and access tokens live in the dropped tables and stop
 * working at once. Application Passwords belong to users and
 * are left in place; revoke them under Users → Profile.
 *
 * @package AcMcp
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'acmcp_audit', 'acmcp_snapshots', 'acmcp_oauth_clients', 'acmcp_tokens' ) as $ac_mcp_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $ac_mcp_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables.
}

delete_option( 'ac_mcp_settings' );
delete_option( 'ac_mcp_db_version' );
delete_option( 'ac_mcp_migrated_from_mcp_builder' );
wp_clear_scheduled_hook( 'ac_mcp_prune' );
