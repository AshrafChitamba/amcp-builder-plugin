<?php
/**
 * The elementor guide resource.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at amcp-builder://guides/elementor.
 */
final class ElementorGuideResource extends GuideResource {

	protected function topic(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return null !== $this->environment->elementor_version();
	}
}
