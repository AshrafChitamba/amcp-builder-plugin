<?php
/**
 * The bricks guide resource.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at amcp-builder://guides/bricks.
 */
final class BricksGuideResource extends GuideResource {

	protected function topic(): string {
		return 'bricks';
	}

	public function is_available(): bool {
		return null !== $this->environment->bricks_version();
	}
}
