<?php
/**
 * Removes AMCP Builder's settings, tables and scheduled job when the plugin is deleted.
 *
 * OAuth connections and access tokens live in the dropped tables and stop
 * working at once. Application Passwords belong to users and
 * are left in place; revoke them under Users → Profile.
 *
 * @package AmcpBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'amcpb_audit', 'amcpb_snapshots', 'amcpb_oauth_clients', 'amcpb_tokens' ) as $amcp_builder_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $amcp_builder_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables.
}

delete_option( 'amcp_builder_settings' );
delete_option( 'amcp_builder_db_version' );
delete_option( 'amcp_builder_migrated_from_mcp_builder' );
wp_clear_scheduled_hook( 'amcp_builder_prune' );
