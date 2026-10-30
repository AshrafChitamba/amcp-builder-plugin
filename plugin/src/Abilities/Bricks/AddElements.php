<?php
/**
 * The bricks-add-elements tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\Ids;
use AcMcp\Bricks\Tree;
use AcMcp\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a nested group of native Bricks elements to a page.
 */
final class AddElements extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-add-elements';
	}

	public function label(): string {
		return __( 'Bricks: add elements', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Adds native Bricks elements to a page in one call. Pass a nested tree: each element has name, optional label, settings and children. '
			. 'IDs are generated for you and returned. Omit parentId to add at the top level of the page, or pass an element ID (it must be a layout element such as section, container, block or div). '
			. 'position is the index among the parent\'s children (omit to append). Build one section per call. '
			. 'Example two-column section: {"name":"section","children":[{"name":"container","settings":{"_display":"grid","_gridTemplateColumns":"1fr 1fr","_gridGap":"40px","_gridTemplateColumns:mobile_portrait":"1fr"},"children":[{"name":"block","children":[{"name":"heading","settings":{"text":"Title","tag":"h2"}},{"name":"text-basic","settings":{"text":"Body"}}]},{"name":"image","settings":{"image":{"id":12,"url":"https://…","size":"full"}}}]}]}. '
			. 'Grid item spans are raw CSS: use "_gridItemColumnSpan":"span 2", not "2". '
			. 'Unknown element names or settings keys are rejected: check them with bricks-list-elements and bricks-get-element-schema. A snapshot is taken before saving.';
	}

	protected function change_properties(): array {
		return array(
			'parentId' => array(
				'type'        => 'string',
				'description' => 'Element to add into. Omit for the top level.',
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
						'name'     => array( 'type' => 'string' ),
						'label'    => array( 'type' => 'string' ),
						'settings' => array( 'type' => 'object' ),
						'children' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
			),
		);
	}

	protected function change_required(): array {
		return array( 'elements' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$parent = self::is_root( $input['parentId'] ?? null ) ? 0 : (string) $input['parentId'];
		$validator->specs( $input['elements'], self::parent_name( $tree, $parent ) );

		$before  = $tree->children( $parent );
		$created = $tree->insert( $input['elements'], $parent, $input['position'] ?? null, new Ids( $tree->ids() ) );

		return array(
			'createdIds' => $created,
			'rootIds'    => array_values( array_diff( $tree->children( $parent ), $before ) ),
		);
	}
}
