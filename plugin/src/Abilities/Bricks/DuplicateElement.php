<?php
/**
 * The bricks-duplicate-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\Ids;
use AcMcp\Bricks\Tree;
use AcMcp\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Copies an element and its children.
 */
final class DuplicateElement extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-duplicate-element';
	}

	public function label(): string {
		return __( 'Bricks: duplicate element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Copies an element and everything inside it, placing the copy right after the original. Every copied element gets a new ID; the new top ID is returned. '
			. 'Useful for repeating cards or sections, then changing their text with bricks-update-element.';
	}

	protected function change_properties(): array {
		return array( 'elementId' => array( 'type' => 'string' ) );
	}

	protected function change_required(): array {
		return array( 'elementId' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$copy = $tree->duplicate( (string) $input['elementId'], new Ids( $tree->ids() ) );
		return array(
			'newId'      => $copy['id'],
			'createdIds' => $copy['created'],
		);
	}
}
