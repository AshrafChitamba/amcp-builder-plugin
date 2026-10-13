<?php
/**
 * The safety guide resource.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at mcp-builder://guides/safety.
 */
final class SafetyGuideResource extends GuideResource {

	protected function topic(): string {
		return 'safety';
	}
}
