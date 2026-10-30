<?php
/**
 * Plugin bootstrap.
 *
 * @package AcMcp
 */

namespace AcMcp;

use AcMcp\Abilities\Registry;
use AcMcp\Admin\AdminPage;
use AcMcp\Admin\Notices;
use AcMcp\Mcp\Server;
use AcMcp\OAuth\Clients;
use AcMcp\OAuth\Server as OAuthServer;
use AcMcp\OAuth\Tokens;
use AcMcp\Rest\AdminController;
use AcMcp\Safety\AuditLog;
use AcMcp\Safety\Governance;
use AcMcp\Safety\RateLimiter;
use AcMcp\Safety\Schema;
use AcMcp\Safety\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services together.
 */
final class Plugin {

	public const SLUG = 'ac-mcp-for-bricks-elementor';

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
		$tokens       = new Tokens();
		$clients      = new Clients();

		Migration::maybe_run();
		Schema::maybe_install();
		Installer::schedule();
		add_action(
			Installer::CRON_HOOK,
			static function () use ( $settings, $audit, $snapshots, $tokens, $clients ): void {
				self::prune( $settings, $audit, $snapshots );
				$tokens->prune();
				$clients->prune();
			}
		);

		( new Notices( $requirements ) )->register();
		( new AdminPage( $requirements ) )->register();
		( new AdminController( $settings, $environment, $requirements, $abilities, $audit, $snapshots, $tokens ) )->register();

		if ( ! $requirements->can_run() ) {
			return;
		}

		( new Governance( $settings, $abilities, $audit, $snapshots, new RateLimiter() ) )->register();
		( new OAuthServer( $settings, $clients, $tokens ) )->register();
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
