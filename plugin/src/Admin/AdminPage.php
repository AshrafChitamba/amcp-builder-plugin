<?php
/**
 * The AC MCP admin menu and screen.
 *
 * @package AcMcp
 */

namespace AcMcp\Admin;

use AcMcp\Mcp\Server;
use AcMcp\Plugin;
use AcMcp\Requirements;
use AcMcp\Rest\AdminController;

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

	/**
	 * Tutorial video shown on the Tutorial screen. Paste a YouTube, Vimeo,
	 * Loom or Google Drive link here (Drive files must be shared as
	 * "Anyone with the link"). Sites can override it with the
	 * ac_mcp_tutorial_url filter.
	 */
	public const TUTORIAL_URL = '';

	/** Screens of the app, in menu order: tab => menu label. */
	private const SCREENS = array(
		'connection' => 'Connection',
		'design'     => 'Design rules',
		'tools'      => 'Tools',
		'safety'     => 'Safety & logs',
		'status'     => 'Status',
		'tutorial'   => 'Tutorial',
		'about'      => 'About',
	);

	/** Menu icon: the AC MCP mark, a monochrome SVG that WordPress tints to the admin color scheme. */
	private const ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M8.6 2.4h2.8l5.6 13.9h-3l-1.2-3.1H7.2L6 16.3H3zm1.4 3.5L8.1 10.9h3.8z"/><circle fill="black" cx="3.2" cy="17.4" r="1.6"/><circle fill="black" cx="16.8" cy="17.4" r="1.6"/><circle fill="black" cx="10" cy="2.3" r="1.6"/></svg>';

	private string $hook = '';

	public function __construct( private Requirements $requirements ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( AC_MCP_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void {
		$this->hook = (string) add_menu_page(
			__( 'AC MCP for Bricks and Elementor', 'ac-mcp-for-bricks-elementor' ),
			__( 'AC MCP', 'ac-mcp-for-bricks-elementor' ),
			'manage_options',
			Plugin::SLUG,
			array( $this, 'render' ),
			'data:image/svg+xml;base64,' . base64_encode( self::ICON ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Menu icons are passed as data URIs.
			58.7
		);

		foreach ( self::SCREENS as $tab => $label ) {
			add_submenu_page(
				Plugin::SLUG,
				self::screen_label( $label ),
				self::screen_label( $label ),
				'manage_options',
				'connection' === $tab ? Plugin::SLUG : 'admin.php?page=' . Plugin::SLUG . '&tab=' . $tab
			);
		}
		add_filter( 'submenu_file', array( $this, 'current_submenu' ) );
	}

	/**
	 * Highlights the submenu item of the open tab.
	 *
	 * @param string|null $file Current submenu file.
	 */
	public function current_submenu( $file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only menu highlighting.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only menu highlighting.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( Plugin::SLUG !== $page || '' === $tab || 'connection' === $tab || ! isset( self::SCREENS[ $tab ] ) ) {
			return $file;
		}
		return 'admin.php?page=' . Plugin::SLUG . '&tab=' . $tab;
	}

	/**
	 * Translated menu label.
	 */
	private static function screen_label( string $label ): string {
		$labels = array(
			'Connection'    => __( 'Connection', 'ac-mcp-for-bricks-elementor' ),
			'Design rules'  => __( 'Design rules', 'ac-mcp-for-bricks-elementor' ),
			'Tools'         => __( 'Tools', 'ac-mcp-for-bricks-elementor' ),
			'Safety & logs' => __( 'Safety & logs', 'ac-mcp-for-bricks-elementor' ),
			'Status'        => __( 'Status', 'ac-mcp-for-bricks-elementor' ),
			'Tutorial'      => __( 'Tutorial', 'ac-mcp-for-bricks-elementor' ),
			'About'         => __( 'About', 'ac-mcp-for-bricks-elementor' ),
		);
		return $labels[ $label ] ?? $label;
	}

	/**
	 * Tutorial video link, overridable per site.
	 */
	public static function tutorial_url(): string {
		return esc_url_raw( (string) apply_filters( 'ac_mcp_tutorial_url', self::TUTORIAL_URL ) );
	}

	/**
	 * @param array<string, string> $links Existing links.
	 * @return array<string, string>
	 */
	public function action_links( array $links ): array {
		$links['settings'] = sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Settings', 'ac-mcp-for-bricks-elementor' ) );
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
			admin_url( 'admin.php' )
		);
	}

	public function render(): void {
		echo '<div class="wrap acmcp-wrap"><div id="ac-mcp-root"><p>' . esc_html__( 'Loading…', 'ac-mcp-for-bricks-elementor' ) . '</p></div></div>';
	}

	public function enqueue( string $hook ): void {
		if ( $hook !== $this->hook ) {
			return;
		}

		$asset_file = AC_MCP_DIR . 'build/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			wp_admin_notice( esc_html__( 'AC MCP admin assets are missing. Run "npm run build" in admin-ui, or install the release ZIP.', 'ac-mcp-for-bricks-elementor' ), array( 'type' => 'error' ) );
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script( 'ac-mcp-admin', AC_MCP_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'ac-mcp-admin', AC_MCP_URL . 'build/style-index.css', array( 'wp-components' ), $asset['version'] );
		wp_style_add_data( 'ac-mcp-admin', 'rtl', 'replace' );
		wp_set_script_translations( 'ac-mcp-admin', 'ac-mcp-for-bricks-elementor', AC_MCP_DIR . 'languages' );

		$user = wp_get_current_user();
		wp_add_inline_script(
			'ac-mcp-admin',
			'window.acMcp = ' . wp_json_encode(
				array(
					'version'       => AC_MCP_VERSION,
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
					'tutorialUrl'   => self::tutorial_url(),
					'adminUrl'      => admin_url( 'admin.php?page=' . Plugin::SLUG ),
				)
			) . ';',
			'before'
		);
	}
}
