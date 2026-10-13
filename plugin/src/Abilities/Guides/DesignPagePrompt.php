<?php
/**
 * The design-page prompt.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Guides;

use McpBuilder\Abilities\Ability;
use McpBuilder\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * A ready-made instruction for designing or redesigning a page with good practice.
 */
final class DesignPagePrompt extends Ability {

	public function slug(): string {
		return 'design-page';
	}

	public function label(): string {
		return __( 'Design a page', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Design or redesign a page on this site following the site\'s design system and MCP Builder\'s design guide.';
	}

	public function mcp_type(): string {
		return 'prompt';
	}

	protected function properties(): array {
		return array(
			'brief'  => array(
				'type'        => 'string',
				'description' => 'What the page is for and who it is for.',
			),
			'postId' => array(
				'type'        => 'string',
				'description' => 'Existing page ID to work on. Leave empty to create a new draft.',
			),
		);
	}

	protected function required(): array {
		return array( 'brief' );
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	protected function run( array $input ): array {
		$target = empty( $input['postId'] )
			? 'Create a new draft page with create-page.'
			: sprintf( 'Work on page %d. Read it first, and do not touch other pages.', (int) $input['postId'] );

		return array(
			'text' => implode(
				"\n\n",
				array(
					'Design a page on this WordPress site with MCP Builder.',
					'Brief: ' . sanitize_textarea_field( (string) $input['brief'] ),
					$target,
					"Steps:\n1. Call site-info to see which builder is active and the site's safety settings.\n2. Read the builder guide (get-design-guide with the builder's topic) and look at one existing page and the global styles, so you reuse the site's colors, fonts and classes.\n3. Write a one-line design read and a short section plan, and show it to the user before building if the change is large.\n4. Build one section per call. Use dryRun for big changes. Use only real content and real media; mark placeholders.\n5. Read the page back, then summarize what you built, what is a placeholder and how to undo it (restore-snapshot).",
					( new Guides( $this->settings ) )->get( 'design' ),
				)
			),
		);
	}
}
