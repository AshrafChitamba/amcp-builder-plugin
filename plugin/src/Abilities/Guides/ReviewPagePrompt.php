<?php
/**
 * The review-page prompt.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

use AcMcp\Abilities\Ability;
use AcMcp\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * A ready-made instruction for auditing a page against the design guide.
 */
final class ReviewPagePrompt extends Ability {

	public function slug(): string {
		return 'review-page';
	}

	public function label(): string {
		return __( 'Review a page', 'ac-mcp-for-bricks-elementor' );
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
					"Read the page with the builder's get-page tool and check it against the guide below: the site owner's rules at the end of the guide first, then hierarchy, the hero (does it reflect this business?), eyebrow labels above headings (remove them), layout variety, mobile fallbacks, contrast, copy (no filler, no invented facts, no em-dashes), real links and images, and consistency with the rest of the site (no new colors, fonts, spacing or radius).\nReport the problems ordered by impact, each with the element ID and the exact fix you propose. Ask the user which fixes to apply.",
					( new Guides( $this->settings ) )->get( 'design' ),
				)
			),
		);
	}
}
