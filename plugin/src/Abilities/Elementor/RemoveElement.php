<?php
/**
 * The elementor-remove-element tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Elementor\Tree;
use McpBuilder\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Deletes an element and its children.
 */
final class RemoveElement extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-remove-element';
	}

	public function label(): string {
		return __( 'Elementor: remove element', 'mcp-builder' );
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
