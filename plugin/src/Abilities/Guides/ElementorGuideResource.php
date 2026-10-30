<?php
/**
 * The elementor guide resource.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at ac-mcp://guides/elementor.
 */
final class ElementorGuideResource extends GuideResource {

	protected function topic(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return null !== $this->environment->elementor_version();
	}
}
