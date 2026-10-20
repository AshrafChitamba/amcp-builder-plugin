<?php
/**
 * The elementor-add-elements tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Elementor;

use AmcpBuilder\Elementor\Ids;
use AmcpBuilder\Elementor\Tree;
use AmcpBuilder\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a nested group of classic Elementor elements to a page.
 */
final class AddElements extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-add-elements';
	}

	public function label(): string {
		return __( 'Elementor: add elements', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Adds classic Elementor elements (Containers and widgets) to a page in one call. Pass a nested tree: each element has elType ("container" or "widget"), widgetType for widgets, settings, and child elements. '
			. 'IDs are generated for you and returned. Omit parentId to add at the top level (only containers go there), or pass a container ID. position is the index among the parent\'s children (omit to append). Build one section per call. '
			. 'Example two-column section: {"elType":"container","settings":{"content_width":"boxed","flex_direction":"row","flex_gap":{"unit":"px","column":"48","row":"48","isLinked":true},"flex_direction_mobile":"column","padding":{"unit":"px","top":"96","right":"24","bottom":"96","left":"24","isLinked":false}},"elements":['
			. '{"elType":"container","settings":{"width":{"unit":"%","size":50}},"elements":[{"elType":"widget","widgetType":"heading","settings":{"title":"Title","header_size":"h2"}},{"elType":"widget","widgetType":"text-editor","settings":{"editor":"<p>Body</p>"}}]},'
			. '{"elType":"container","elements":[{"elType":"widget","widgetType":"image","settings":{"image":{"url":"https://…","id":""}}}]}]}. '
			. 'Styling groups need their switch key: "typography_typography":"custom" before "typography_font_size", "background_background":"classic" before "background_color". Responsive values use a device suffix, e.g. "padding_mobile". '
			. 'Unknown widgets or settings keys are rejected: check them with elementor-list-widgets and elementor-get-widget-schema. A snapshot is taken before saving.';
	}

	protected function change_properties(): array {
		return array(
			'parentId' => array(
				'type'        => 'string',
				'description' => 'Container to add into. Omit for the top level.',
			),
			'position' => array(
				'type'    => 'integer',
				'minimum' => 0,
			),
			'elements' => array(
				'type'     => 'array',
				'minItems' => 1,
				'maxItems' => 50,
				'items'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'elType'     => array(
							'type' => 'string',
							'enum' => array( 'container', 'widget', 'section', 'column' ),
						),
						'widgetType' => array( 'type' => 'string' ),
						'settings'   => array( 'type' => 'object' ),
						'elements'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'             => array( 'elType' ),
					'additionalProperties' => false,
				),
			),
		);
	}

	protected function change_required(): array {
		return array( 'elements' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$parent    = self::parent_element( $tree, $input['parentId'] ?? null );
		$parent_id = null === $parent ? null : (string) $parent['id'];
		$validator->specs( $input['elements'], $parent );

		$before  = $tree->children( $parent_id );
		$created = $tree->insert( $input['elements'], $parent_id, $input['position'] ?? null, new Ids( $tree->ids() ) );

		return array(
			'createdIds' => $created,
			'rootIds'    => array_values( array_diff( $tree->children( $parent_id ), $before ) ),
		);
	}
}
