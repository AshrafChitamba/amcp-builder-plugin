<?php
/**
 * Custom-code policy.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Support;

use McpBuilder\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Elements that hold raw HTML, JavaScript or PHP (the Bricks Code element,
 * Bricks SVG code, the Elementor HTML widget) need the custom-code switch and
 * an administrator account that may post unfiltered HTML.
 */
final class CustomCode {

	public static function allowed( Settings $settings ): bool {
		return (bool) $settings->get( 'custom_code_enabled' ) && current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	}

	public static function refusal( string $what ): TreeError {
		return new TreeError(
			'custom_code_disabled',
			sprintf( 'This needs custom code (%s), and custom code is switched off or not allowed for this account.', $what ),
			'Build it with native elements instead. If custom code is really needed, ask an administrator to enable "Allow custom code" under Settings → MCP Builder → Tools and to use an administrator account.'
		);
	}
}
