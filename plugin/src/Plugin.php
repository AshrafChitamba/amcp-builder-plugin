<?php
/**
 * Plugin bootstrap.
 *
 * @package McpBuilder
 */

namespace McpBuilder;

use McpBuilder\Abilities\Registry;
use McpBuilder\Admin\AdminPage;
use McpBuilder\Admin\Notices;
use McpBuilder\Mcp\Server;
use McpBuilder\Rest\AdminController;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services together.
 */
final class Plugin {

	public const SLUG = 'mcp-builder';

	private static ?Plugin $instance = null;

	private bool $booted = false;

	/**
	 * Returns the shared instance.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Registers every hook. Runs on plugins_loaded.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		$settings     = new Settings();
		$environment  = new Environment();
		$requirements = new Requirements( $environment );
		$abilities    = new Registry( $settings, $environment );
		$server       = new Server( $settings, $abilities );

		( new Notices( $requirements ) )->register();
		( new AdminPage( $requirements ) )->register();
		( new AdminController( $settings, $environment, $requirements, $abilities ) )->register();

		if ( ! $requirements->can_run() ) {
			return;
		}

		$abilities->register();
		$server->register();
	}
}
