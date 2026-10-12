<?php
/**
 * Settings → MCP Builder screen.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Admin;

use McpBuilder\Mcp\Server;
use McpBuilder\Plugin;
use McpBuilder\Requirements;
use McpBuilder\Rest\AdminController;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the screen and boots the React app on it.
 */
final class AdminPage {

	/** Groups the Application Passwords this plugin creates. */
	public const APP_ID = '6f1d2c3a-8b4e-4f7a-9c2d-5e8b1a3f7d40';

	public const AUTHOR     = 'Ashraf Chitambaa';
	public const AUTHOR_URL = 'https://ashrafchitambaa.com';
	public const DOCS_URL   = 'https://ashrafchitambaa.com';

	private string $hook = '';

	public function __construct( private Requirements $requirements ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MCP_BUILDER_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void {
		$this->hook = (string) add_options_page(
			__( 'MCP Builder', 'mcp-builder' ),
			__( 'MCP Builder', 'mcp-builder' ),
			'manage_options',
			Plugin::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * @param array<string, string> $links Existing links.
	 * @return array<string, string>
	 */
	public function action_links( array $links ): array {
		$links['settings'] = sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Settings', 'mcp-builder' ) );
		return $links;
	}

	public static function url( string $tab = '' ): string {
		return add_query_arg(
			array_filter(
				array(
					'page' => Plugin::SLUG,
					'tab'  => $tab,
				)
			),
			admin_url( 'options-general.php' )
		);
	}

	public function render(): void {
		echo '<div class="wrap"><div id="mcp-builder-root"><p>' . esc_html__( 'Loading…', 'mcp-builder' ) . '</p></div></div>';
	}

	public function enqueue( string $hook ): void {
		if ( $hook !== $this->hook ) {
			return;
		}

		$asset_file = MCP_BUILDER_DIR . 'build/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			wp_admin_notice( esc_html__( 'MCP Builder admin assets are missing. Run "npm run build" in admin-ui, or install the release ZIP.', 'mcp-builder' ), array( 'type' => 'error' ) );
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script( 'mcp-builder-admin', MCP_BUILDER_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'mcp-builder-admin', MCP_BUILDER_URL . 'build/style-index.css', array( 'wp-components' ), $asset['version'] );
		wp_style_add_data( 'mcp-builder-admin', 'rtl', 'replace' );
		wp_set_script_translations( 'mcp-builder-admin', 'mcp-builder', MCP_BUILDER_DIR . 'languages' );

		$user = wp_get_current_user();
		wp_add_inline_script(
			'mcp-builder-admin',
			'window.mcpBuilder = ' . wp_json_encode(
				array(
					'version'       => MCP_BUILDER_VERSION,
					'restNamespace' => AdminController::NAMESPACE,
					'mcpUrl'        => Server::url(),
					'siteUrl'       => home_url( '/' ),
					'username'      => $user->user_login,
					'appId'         => self::APP_ID,
					'canRun'        => $this->requirements->can_run(),
					'author'        => self::AUTHOR,
					'authorUrl'     => self::AUTHOR_URL,
					'docsUrl'       => self::DOCS_URL,
					'isLocal'       => 'local' === wp_get_environment_type(),
				)
			) . ';',
			'before'
		);
	}
}
