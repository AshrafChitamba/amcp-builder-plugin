<?php
/**
 * The elementor-remove-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Tree;
use AcMcp\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Deletes an element and its children.
 */
final class RemoveElement extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-remove-element';
	}

	public function label(): string {
		return __( 'Elementor: remove element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Deletes an element and everything inside it from an Elementor page. Check the element ID with elementor-get-page first, and prefer dryRun when unsure. '
			. 'A snapshot is taken first, so restore-snapshot can undo it.';
	}

	protected function is_destructive(): bool {
		return true;
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
		return array( 'removedIds' => $tree->remove( $element['id'] ) );
	}
}
