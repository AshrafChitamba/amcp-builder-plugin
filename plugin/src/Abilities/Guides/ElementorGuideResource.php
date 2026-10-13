<?php
/**
 * The elementor guide resource.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at mcp-builder://guides/elementor.
 */
final class ElementorGuideResource extends GuideResource {

	protected function topic(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return null !== $this->environment->elementor_version();
	}
}
