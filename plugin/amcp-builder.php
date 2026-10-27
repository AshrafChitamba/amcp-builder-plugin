<?php
/**
 * Plugin Name:       AMCP Builder
 * Description:       Lets AI assistants such as Claude, ChatGPT and Codex build and edit Bricks and Elementor pages, templates and design systems through MCP, with read-only mode, snapshots and an audit log.
 * Version:           1.0.0
 * Requires at least: 7.1
 * Requires PHP:      8.1
 * Requires Plugins:  mcp-adapter
 * Author:            Ashraf Chitambaa
 * Author URI:        https://ashrafchitambaa.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       amcp-builder
 * Domain Path:       /languages
 *
 * @package AmcpBuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'AMCP_BUILDER_VERSION', '1.0.0' );
define( 'AMCP_BUILDER_FILE', __FILE__ );
define( 'AMCP_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMCP_BUILDER_URL', plugin_dir_url( __FILE__ ) );

if ( ! is_readable( AMCP_BUILDER_DIR . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'AMCP Builder is missing its vendor folder. Install the release ZIP, or run "composer install" in the plugin folder.', 'amcp-builder' )
			);
		}
	);
	return;
}

require_once AMCP_BUILDER_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( \AmcpBuilder\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \AmcpBuilder\Installer::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \AmcpBuilder\Plugin::instance(), 'boot' ) );
