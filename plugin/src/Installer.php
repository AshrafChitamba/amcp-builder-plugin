<?php
/**
 * Activation routine.
 *
 * @package McpBuilder
 */

namespace McpBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and cleans up persistent data.
 */
final class Installer {

	public static function activate(): void {
		add_option( Settings::OPTION, Settings::DEFAULTS, '', false );
	}
}
