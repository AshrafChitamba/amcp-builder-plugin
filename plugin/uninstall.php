<?php
/**
 * Removes MCP Builder's settings when the plugin is deleted.
 *
 * Application Passwords created from the Connection tab belong to users and
 * are left in place; revoke them under Users → Profile.
 *
 * @package McpBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mcp_builder_settings' );
