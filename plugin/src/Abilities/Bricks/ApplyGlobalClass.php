<?php
/**
 * The bricks-apply-global-class tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use McpBuilder\Bricks\GlobalClasses;
use McpBuilder\Bricks\Tree;
use McpBuilder\Support\TreeError;
use McpBuilder\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Adds or removes global classes on elements.
 */
final class ApplyGlobalClass extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-apply-global-class';
	}

	public function label(): string {
		return __( 'Bricks: apply global class', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Adds global classes to (or removes them from) one or more elements on a Bricks page. Pass classes by name or ID (see bricks-list-global-classes). '
			. 'Create missing classes first with bricks-upsert-global-class.';
	}

	protected function change_properties(): array {
		return array(
			'elementIds' => array(
				'type'     => 'array',
				'minItems' => 1,
				'maxItems' => 100,
				'items'    => array( 'type' => 'string' ),
			),
			'classes'    => array(
				'type'     => 'array',
				'minItems' => 1,
				'maxItems' => 20,
				'items'    => array( 'type' => 'string' ),
			),
			'action'     => array(
				'type'    => 'string',
				'enum'    => array( 'add', 'remove' ),
				'default' => 'add',
			),
		);
	}

	protected function change_required(): array {
		return array( 'elementIds', 'classes' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$class_ids = array();
		foreach ( $input['classes'] as $name ) {
			$class = GlobalClasses::find( (string) $name );
			if ( ! $class ) {
				throw new TreeError( 'class_not_found', sprintf( 'Global class "%s" does not exist.', $name ), 'Create it with bricks-upsert-global-class, or call bricks-list-global-classes.' );
			}
			$class_ids[] = (string) $class['id'];
		}

		foreach ( array_unique( $input['elementIds'] ) as $element_id ) {
			$element = $tree->get( (string) $element_id );
			$current = (array) ( $element['settings']['_cssGlobalClasses'] ?? array() );
			$next    = 'add' === $input['action']
				? array_values( array_unique( array_merge( $current, $class_ids ) ) )
				: array_values( array_diff( $current, $class_ids ) );

			$tree->update( $element['id'], array( '_cssGlobalClasses' => $next ? $next : null ) );
		}

		return array( 'classIds' => $class_ids );
	}
}
