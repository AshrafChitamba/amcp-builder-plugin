<?php
/**
 * The safety guide resource.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at amcp-builder://guides/safety.
 */
final class SafetyGuideResource extends GuideResource {

	protected function topic(): string {
		return 'safety';
	}
}
