<?php
/**
 * The design guide resource.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at amcp-builder://guides/design.
 */
final class DesignGuideResource extends GuideResource {

	protected function topic(): string {
		return 'design';
	}
}
