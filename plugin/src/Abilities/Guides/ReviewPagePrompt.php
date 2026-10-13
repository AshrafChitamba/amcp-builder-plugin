<?php
/**
 * The review-page prompt.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Guides;

use McpBuilder\Abilities\Ability;
use McpBuilder\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * A ready-made instruction for auditing a page against the design guide.
 */
final class ReviewPagePrompt extends Ability {

	public function slug(): string {
		return 'review-page';
	}

	public function label(): string {
		return __( 'Review a page', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Review an existing page against the design guide and list concrete improvements before changing anything.';
	}

	public function mcp_type(): string {
		return 'prompt';
	}

	protected function properties(): array {
		return array(
			'postId' => array(
				'type'        => 'string',
				'description' => 'Page ID to review.',
			),
		);
	}

	protected function required(): array {
		return array( 'postId' );
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	protected function run( array $input ): array {
		return array(
			'text' => implode(
				"\n\n",
				array(
					sprintf( 'Review page %d on this WordPress site. Do not change anything yet.', (int) $input['postId'] ),
					"Read the page with the builder's get-page tool and check it against the guide below: hierarchy, layout variety, mobile fallbacks, contrast, copy (no filler, no invented facts, no em-dashes), real links and images, and reuse of the site's design system.\nReport the problems ordered by impact, each with the element ID and the exact fix you propose. Ask the user which fixes to apply.",
					( new Guides( $this->settings ) )->get( 'design' ),
				)
			),
		);
	}
}
