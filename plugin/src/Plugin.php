<?php
/**
 * Plugin bootstrap.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder;

use AmcpBuilder\Abilities\Registry;
use AmcpBuilder\Admin\AdminPage;
use AmcpBuilder\Admin\Notices;
use AmcpBuilder\Mcp\Server;
use AmcpBuilder\OAuth\Clients;
use AmcpBuilder\OAuth\Server as OAuthServer;
use AmcpBuilder\OAuth\Tokens;
use AmcpBuilder\Rest\AdminController;
use AmcpBuilder\Safety\AuditLog;
use AmcpBuilder\Safety\Governance;
use AmcpBuilder\Safety\RateLimiter;
use AmcpBuilder\Safety\Schema;
use AmcpBuilder\Safety\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services together.
 */
final class Plugin {

	public const SLUG = 'amcp-builder';

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
