<?php
/**
 * The get-design-guide tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Guides;

use AcMcp\Abilities\Ability;
use AcMcp\Guides\Guides;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the design, builder or safety guide, plus the site's own rules.
 */
final class GetDesignGuide extends Ability {

	public function slug(): string {
		return 'get-design-guide';
	}

	public function label(): string {
		return __( 'Get design guide', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Returns guidance to follow while building, as markdown. Call it once at the start of every build or redesign: topic "design" (layout, hierarchy, copy and accessibility rules plus this site\'s own guidelines), '
			. 'then the topic for the active builder ("bricks" or "elementor": data formats, pitfalls and the right tools). "safety" explains snapshots, dry runs and limits. '
			. 'The site owner\'s guidelines come last and take priority over the general advice. Call it again whenever a result\'s guidelinesVersion differs from the version returned here: the owner has changed the rules.';
	}

	protected function properties(): array {
		return array(
			'topic' => array(
				'type'    => 'string',
				'enum'    => array_merge( Guides::TOPICS, array( 'site' ) ),
				'default' => 'design',
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	protected function run( array $input ): array {
		return array(
			'topic' => $input['topic'],
			'guide' => ( new Guides( $this->settings ) )->get( $input['topic'] ),
			// guidelinesVersion is added to every result by Ability::execute().
		);
	}
}
