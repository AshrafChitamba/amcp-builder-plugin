<?php
/**
 * The bricks-move-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\Tree;
use AcMcp\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Moves or reorders an element and its children.
 */
final class MoveElement extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-move-element';
	}

	public function label(): string {
		return __( 'Bricks: move element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Moves an element (with everything inside it) to another parent and/or position on the same Bricks page. '
			. 'parentId "root" moves it to the top level; omit parentId to only reorder within the current parent. position is the new index among the siblings (0 = first).';
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

		if ( ! array_key_exists( 'parentId', $input ) ) {
			$parent = $element['parent'];
		} elseif ( self::is_root( $input['parentId'] ) ) {
			$parent = 0;
		} else {
			$parent = (string) $input['parentId'];
		}

		if ( 0 !== $parent && $parent !== $element['parent'] ) {
			$validator->specs( array( array( 'name' => $element['name'] ) ), self::parent_name( $tree, $parent ) );
		}

		$tree->move( $element['id'], $parent, $input['position'] ?? null );
		return array( 'elementId' => $element['id'] );
	}
}
