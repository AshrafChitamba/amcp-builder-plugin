<?php
/**
 * The safety guide resource.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at ac-mcp://guides/safety.
 */
final class SafetyGuideResource extends GuideResource {

	protected function topic(): string {
		return 'safety';
	}
}
