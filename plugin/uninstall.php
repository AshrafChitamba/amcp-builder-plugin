<?php
/**
 * Removes MCP Builder's settings, tables and scheduled job when the plugin is deleted.
 *
 * OAuth connections and access tokens live in the dropped tables and stop
 * working at once. Application Passwords belong to users and
 * are left in place; revoke them under Users → Profile.
 *
 * @package McpBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'mcpb_audit', 'mcpb_snapshots', 'mcpb_oauth_clients', 'mcpb_tokens' ) as $mcp_builder_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $mcp_builder_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables.
}

delete_option( 'mcp_builder_settings' );
delete_option( 'mcp_builder_db_version' );
wp_clear_scheduled_hook( 'mcp_builder_prune' );
