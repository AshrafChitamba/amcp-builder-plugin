<?php
/**
 * Removes MCP Builder's settings, tables and scheduled job when the plugin is deleted.
 *
 * Application Passwords created from the Connection tab belong to users and
 * are left in place; revoke them under Users → Profile.
 *
 * @package McpBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'mcpb_audit', 'mcpb_snapshots' ) as $mcp_builder_table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $mcp_builder_table ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- Plugin-owned table names.
}

delete_option( 'mcp_builder_settings' );
delete_option( 'mcp_builder_db_version' );
wp_clear_scheduled_hook( 'mcp_builder_prune' );
