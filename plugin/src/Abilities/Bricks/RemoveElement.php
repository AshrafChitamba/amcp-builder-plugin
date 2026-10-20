<?php
/**
 * The bricks-remove-element tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

use AmcpBuilder\Bricks\Tree;
use AmcpBuilder\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Deletes an element and its children.
 */
final class RemoveElement extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-remove-element';
	}

	public function label(): string {
		return __( 'Bricks: remove element', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Deletes an element and everything inside it from a Bricks page. Check the element ID with bricks-get-page first, and prefer dryRun when unsure. '
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
		return array( 'removedIds' => $tree->remove( (string) $input['elementId'] ) );
	}
}
