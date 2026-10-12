<?php
/**
 * Plugin Name:       MCP Builder
 * Plugin URI:        https://ashrafchitambaa.com
 * Description:       Lets AI clients such as Claude and Cursor build and edit Bricks and Elementor pages through MCP, with read-only mode, snapshots and an audit log.
 * Version:           0.1.0
 * Requires at least: 7.1
 * Requires PHP:      8.1
 * Requires Plugins:  mcp-adapter
 * Author:            Ashraf Chitambaa
 * Author URI:        https://ashrafchitambaa.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mcp-builder
 * Domain Path:       /languages
 *
 * @package McpBuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'MCP_BUILDER_VERSION', '0.1.0' );
define( 'MCP_BUILDER_FILE', __FILE__ );
define( 'MCP_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCP_BUILDER_URL', plugin_dir_url( __FILE__ ) );

if ( ! is_readable( MCP_BUILDER_DIR . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'MCP Builder is missing its vendor folder. Install the release ZIP, or run "composer install" in the plugin folder.', 'mcp-builder' )
			);
		}
	);
	return;
}

require_once MCP_BUILDER_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( \McpBuilder\Installer::class, 'activate' ) );

add_action( 'plugins_loaded', array( \McpBuilder\Plugin::instance(), 'boot' ) );
