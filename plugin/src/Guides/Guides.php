<?php
/**
 * Builder guidance shared with AI clients.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Guides;

use McpBuilder\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the guides shipped in /guides and adds the site owner's own rules.
 *
 * The same text is offered three ways because MCP clients differ: as MCP
 * resources, as MCP prompts and through the get-design-guide tool, which
 * every client supports.
 */
final class Guides {

	public const TOPICS = array( 'design', 'bricks', 'elementor', 'safety' );

	public function __construct( private Settings $settings ) {}

	/**
	 * Markdown for one topic, followed by the site's own guidelines.
	 */
	public function get( string $topic ): string {
		$parts = array();

		$builtin = 'design' !== $topic || $this->settings->get( 'design_guide' );
		if ( $builtin && in_array( $topic, self::TOPICS, true ) ) {
			$parts[] = $this->file( $topic );
		}

		$custom = trim( (string) $this->settings->get( 'design_guidelines' ) );
		if ( '' !== $custom && in_array( $topic, array( 'design', 'site' ), true ) ) {
			$parts[] = "# This site's own guidelines\n\nThe site owner wrote these. They take priority over the general guide where they differ.\n\n" . $custom;
		}

		if ( ! $parts ) {
			return 'No guidelines are configured for this topic.';
		}
		return implode( "\n\n---\n\n", $parts );
	}

	private function file( string $topic ): string {
		$path = MCP_BUILDER_DIR . 'guides/' . $topic . '.md';
		$text = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		return false === $text ? '' : $text;
	}
}
