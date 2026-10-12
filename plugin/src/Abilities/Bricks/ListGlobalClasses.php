<?php
/**
 * The bricks-list-global-classes tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

use McpBuilder\Bricks\GlobalClasses;
use McpBuilder\Support\Untrusted;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the site's Bricks global classes.
 */
final class ListGlobalClasses extends BricksAbility {

	public function slug(): string {
		return 'bricks-list-global-classes';
	}

	public function label(): string {
		return __( 'Bricks: list global classes', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Lists Bricks global classes (reusable styles) with id, name, category and which style keys each class sets. '
			. 'Prefer reusing existing classes for consistent design: apply them with bricks-apply-global-class instead of repeating styles on every element.';
	}

	protected function properties(): array {
		return array(
			'search' => array(
				'type'      => 'string',
				'maxLength' => 60,
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		$search  = strtolower( (string) ( $input['search'] ?? '' ) );
		$classes = array();
		foreach ( GlobalClasses::all() as $class ) {
			$name = (string) ( $class['name'] ?? '' );
			if ( '' !== $search && ! str_contains( strtolower( $name ), $search ) ) {
				continue;
			}
			$classes[] = array(
				'id'          => (string) ( $class['id'] ?? '' ),
				'name'        => Untrusted::text( $name, 100 ),
				'category'    => (string) ( $class['category'] ?? '' ),
				'settingKeys' => array_keys( (array) ( $class['settings'] ?? array() ) ),
			);
		}
		return array(
			'classes' => $classes,
			'total'   => count( $classes ),
		);
	}
}
