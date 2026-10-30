<?php
/**
 * The bricks-get-element-schema tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\Validator;
use AcMcp\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the settings one element type accepts.
 */
final class GetElementSchema extends BricksAbility {

	public function slug(): string {
		return 'bricks-get-element-schema';
	}

	public function label(): string {
		return __( 'Bricks: get element schema', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Returns every settings key one Bricks element accepts, read from the live site: key, control type, label, tab, allowed options, default, and the shape its value must have. '
			. 'Always call it before setting values on an element type you have not used yet: unknown keys are rejected. '
			. 'Style keys start with "_" (e.g. _padding, _typography, _background). Add a breakpoint suffix for responsive values, e.g. "_padding:mobile_portrait", '
			. 'and a pseudo-class suffix for states, e.g. "_background:hover".';
	}

	protected function properties(): array {
		return array(
			'name' => array(
				'type'        => 'string',
				'maxLength'   => 60,
				'description' => 'Element name from bricks-list-elements, e.g. "heading".',
			),
		);
	}

	protected function required(): array {
		return array( 'name' );
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ) {
		$name = (string) $input['name'];
		if ( ! $this->catalog()->has( $name ) ) {
			return Errors::make( 'unknown_element', sprintf( /* translators: %s: element name. */ __( 'Unknown element "%s".', 'ac-mcp-for-bricks-elementor' ), $name ), __( 'Call bricks-list-elements to see valid names.', 'ac-mcp-for-bricks-elementor' ), 404 );
		}

		// VERIFY: Bricks\Breakpoints::get_breakpoints() returns [ [ 'key' => …, 'width' => … ] ] (Bricks 2.3.1).
		$breakpoints = class_exists( '\Bricks\Breakpoints' ) ? \Bricks\Breakpoints::get_breakpoints() : array();

		return array(
			'name'        => $name,
			'nestable'    => $this->catalog()->nestable( $name ),
			'controls'    => $this->catalog()->schema( $name ),
			'metaKeys'    => Validator::META_KEYS,
			'breakpoints' => array_values(
				array_map(
					static fn( $breakpoint ): array => array(
						'key'   => (string) ( $breakpoint['key'] ?? '' ),
						'width' => (int) ( $breakpoint['width'] ?? 0 ),
					),
					is_array( $breakpoints ) ? $breakpoints : array()
				)
			),
		);
	}
}
