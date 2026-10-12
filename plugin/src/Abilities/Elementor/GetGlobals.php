<?php
/**
 * The elementor-get-globals tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Elementor\Globals;
use McpBuilder\Support\Untrusted;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the site's global colors and fonts.
 */
final class GetGlobals extends ElementorAbility {

	public function slug(): string {
		return 'elementor-get-globals';
	}

	public function label(): string {
		return __( 'Elementor: get globals', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Returns the site\'s global colors and typography from the active Elementor kit (Site Settings), each with its id, value and the reference string to use in __globals__. '
			. 'Prefer globals over hard-coded values so the page follows the site\'s design system: e.g. {"__globals__":{"title_color":"globals/colors?id=primary","typography_typography":"globals/typography?id=primary"}}.';
	}

	protected function properties(): array {
		return array();
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		return Globals::read() + array( 'notice' => Untrusted::NOTICE );
	}
}
