<?php
/**
 * Guides exposed as MCP resources.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

use AcMcp\Abilities\Ability;
use AcMcp\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * One guide topic as a read-only markdown resource at ac-mcp://guides/{topic}.
 */
abstract class GuideResource extends Ability {

	abstract protected function topic(): string;

	public function slug(): string {
		return 'guide-' . $this->topic();
	}

	public function label(): string {
		/* translators: %s: guide topic. */
		return sprintf( __( 'Guide: %s', 'ac-mcp-for-bricks-elementor' ), ucfirst( $this->topic() ) );
	}

	protected function description(): string {
		return 'AC MCP for Bricks and Elementor ' . $this->topic() . ' guide (markdown). Same content as get-design-guide with topic "' . $this->topic() . '".';
	}

	public function mcp_type(): string {
		return 'resource';
	}

	protected function mcp_meta(): array {
		return array(
			'uri'      => 'ac-mcp://guides/' . $this->topic(),
			'mimeType' => 'text/markdown',
		);
	}

	protected function properties(): array {
		return array();
	}

	public function is_readonly(): bool {
		return true;
	}

	public function group(): string {
		return 'general';
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * @return string Markdown.
	 */
	protected function run( array $input ) {
		return ( new Guides( $this->settings ) )->get( $this->topic() );
	}
}
