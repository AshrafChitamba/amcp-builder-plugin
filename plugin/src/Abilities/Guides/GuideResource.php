<?php
/**
 * Guides exposed as MCP resources.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Guides;

use AmcpBuilder\Abilities\Ability;
use AmcpBuilder\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * One guide topic as a read-only markdown resource at amcp-builder://guides/{topic}.
 */
abstract class GuideResource extends Ability {

	abstract protected function topic(): string;

	public function slug(): string {
		return 'guide-' . $this->topic();
	}

	public function label(): string {
		/* translators: %s: guide topic. */
		return sprintf( __( 'Guide: %s', 'amcp-builder' ), ucfirst( $this->topic() ) );
	}

	protected function description(): string {
		return 'AMCP Builder ' . $this->topic() . ' guide (markdown). Same content as get-design-guide with topic "' . $this->topic() . '".';
	}

	public function mcp_type(): string {
		return 'resource';
	}

	protected function mcp_meta(): array {
		return array(
			'uri'      => 'amcp-builder://guides/' . $this->topic(),
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
