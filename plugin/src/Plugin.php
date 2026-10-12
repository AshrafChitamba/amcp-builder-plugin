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
use McpBuilder\Safety\AuditLog;
use McpBuilder\Safety\Governance;
use McpBuilder\Safety\RateLimiter;
use McpBuilder\Safety\Schema;
use McpBuilder\Safety\Snapshots;

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
		$audit        = new AuditLog();
		$snapshots    = new Snapshots();
		$abilities    = new Registry( $settings, $environment, $snapshots );
		$server       = new Server( $settings, $abilities );

		Schema::maybe_install();
		Installer::schedule();
		add_action( Installer::CRON_HOOK, fn() => self::prune( $settings, $audit, $snapshots ) );

		( new Notices( $requirements ) )->register();
		( new AdminPage( $requirements ) )->register();
		( new AdminController( $settings, $environment, $requirements, $abilities, $audit, $snapshots ) )->register();

		if ( ! $requirements->can_run() ) {
			return;
		}

		( new Governance( $settings, $abilities, $audit, $snapshots, new RateLimiter() ) )->register();
		$abilities->register();
		$server->register();
	}

	/**
	 * Daily cleanup of expired audit entries and snapshots.
	 */
	private static function prune( Settings $settings, AuditLog $audit, Snapshots $snapshots ): void {
		$audit->prune( (int) $settings->get( 'audit_days' ) );
		$snapshots->prune( (int) $settings->get( 'snapshot_keep' ), (int) $settings->get( 'snapshot_days' ) );
	}
}
