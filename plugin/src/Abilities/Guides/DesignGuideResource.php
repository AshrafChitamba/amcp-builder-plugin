<?php
/**
 * The design guide resource.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Resource at mcp-builder://guides/design.
 */
final class DesignGuideResource extends GuideResource {

	protected function topic(): string {
		return 'design';
	}
}
