<?php
/**
 * Activation and deactivation routines.
 *
 * @package AcMcp
 */

namespace AcMcp;

use AcMcp\Safety\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Creates tables, default settings and the daily cleanup job.
 */
final class Installer {

	public const CRON_HOOK = 'ac_mcp_prune';

	public static function activate(): void {
		Migration::maybe_run();
		add_option( Settings::OPTION, Settings::DEFAULTS, '', false );
		Schema::maybe_install();
		self::schedule();
	}

	/**
	 * Schedules the daily cleanup if missing (also covers updates without re-activation).
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}
