<?php
/**
 * The design guide resource.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at ac-mcp://guides/design.
 */
final class DesignGuideResource extends GuideResource {

	protected function topic(): string {
		return 'design';
	}
}
