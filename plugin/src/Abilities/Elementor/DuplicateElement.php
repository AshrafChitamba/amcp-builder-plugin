<?php
/**
 * The elementor-duplicate-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Ids;
use AcMcp\Elementor\Tree;
use AcMcp\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Copies an element and its children.
 */
final class DuplicateElement extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-duplicate-element';
	}

	public function label(): string {
		return __( 'Elementor: duplicate element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Copies an element and everything inside it, placing the copy right after the original. Every copied element gets a new ID; the new top ID is returned. '
			. 'Useful for repeating cards or sections, then changing their text with elementor-update-element.';
	}

	protected function change_properties(): array {
		return array( 'elementId' => array( 'type' => 'string' ) );
	}

	protected function change_required(): array {
		return array( 'elementId' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$element = $tree->get( (string) $input['elementId'] );
		$validator->editable( $element );

		$copy = $tree->duplicate( $element['id'], new Ids( $tree->ids() ) );
		return array(
			'newId'      => $copy['id'],
			'createdIds' => $copy['created'],
		);
	}
}
