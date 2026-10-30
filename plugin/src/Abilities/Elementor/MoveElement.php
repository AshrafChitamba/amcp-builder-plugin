<?php
/**
 * The elementor-move-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Tree;
use AcMcp\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Moves or reorders an element and its children.
 */
final class MoveElement extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-move-element';
	}

	public function label(): string {
		return __( 'Elementor: move element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Moves an element (with everything inside it) to another parent and/or position on the same Elementor page. '
			. 'parentId "root" moves it to the top level (containers only); omit parentId to only reorder within the current parent. position is the new index among the siblings (0 = first).';
	}

	protected function change_properties(): array {
		return array(
			'elementId' => array( 'type' => 'string' ),
			'parentId'  => array(
				'type'        => 'string',
				'description' => 'New parent element ID, or "root".',
			),
			'position'  => array(
				'type'    => 'integer',
				'minimum' => 0,
			),
		);
	}

	protected function change_required(): array {
		return array( 'elementId' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$element = $tree->get( (string) $input['elementId'] );
		$parent  = array_key_exists( 'parentId', $input )
			? self::parent_element( $tree, $input['parentId'] )
			: self::parent_element( $tree, $tree->parent_id( $element['id'] ) );

		$validator->placement( $element, $parent );
		$tree->move( $element['id'], null === $parent ? null : (string) $parent['id'], $input['position'] ?? null );

		return array( 'elementId' => $element['id'] );
	}
}
