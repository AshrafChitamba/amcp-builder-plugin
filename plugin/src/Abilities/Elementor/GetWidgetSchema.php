<?php
/**
 * The elementor-get-widget-schema tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Validator;
use AcMcp\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the settings one widget or layout element accepts.
 */
final class GetWidgetSchema extends ElementorAbility {

	public function slug(): string {
		return 'elementor-get-widget-schema';
	}

	public function label(): string {
		return __( 'Elementor: get widget schema', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Returns every settings key one Elementor widget (or "container") accepts, read from the live site: key, control type, label, tab, allowed options, default, units, whether it is responsive, when it is shown, and the shape its value must have. '
			. 'Always call it before setting values on a widget you have not used yet: unknown keys are rejected. '
			. 'groups explains the switch keys that turn styling groups on (typography, background, border, box shadow). Responsive keys add a device suffix from devices, e.g. "padding_mobile". '
			. 'Use the search parameter to shorten the answer, e.g. search "typography" or "color".';
	}

	protected function properties(): array {
		return array(
			'widget' => array(
				'type'        => 'string',
				'maxLength'   => 80,
				'description' => 'Widget name from elementor-list-widgets (e.g. "heading"), or "container".',
			),
			'search' => array(
				'type'        => 'string',
				'maxLength'   => 50,
				'description' => 'Only keys or labels containing this text.',
			),
			'tab'    => array(
				'type'        => 'string',
				'enum'        => array( 'content', 'style', 'advanced', 'layout' ),
				'description' => 'Only controls on this panel tab.',
			),
		);
	}

	protected function required(): array {
		return array( 'widget' );
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ) {
		$name    = (string) $input['widget'];
		$el_type = in_array( $name, array( 'container', 'section', 'column' ), true ) ? $name : 'widget';
		$widget  = 'widget' === $el_type ? $name : null;

		if ( ! $this->catalog()->exists( $el_type, $widget ) ) {
			return Errors::make( 'unknown_widget', sprintf( /* translators: %s: widget name. */ __( 'Unknown widget "%s".', 'ac-mcp-for-bricks-elementor' ), $name ), __( 'Call elementor-list-widgets to see valid names, or use "container".', 'ac-mcp-for-bricks-elementor' ), 404 );
		}

		$schema   = $this->catalog()->schema( $el_type, $widget );
		$search   = strtolower( (string) ( $input['search'] ?? '' ) );
		$controls = array_values(
			array_filter(
				$schema['controls'],
				static fn( array $control ): bool => ( empty( $input['tab'] ) || $control['tab'] === $input['tab'] )
					&& ( '' === $search || str_contains( strtolower( $control['key'] . ' ' . $control['label'] ), $search ) )
			)
		);

		return array(
			'widget'           => $name,
			'elType'           => $el_type,
			'acceptsChildren'  => $this->catalog()->accepts_children( $el_type, $widget ),
			'controlCount'     => count( $controls ),
			'controls'         => $controls,
			'groups'           => (object) $schema['groups'],
			'metaKeys'         => Validator::META_KEYS,
			'devices'          => $this->catalog()->devices(),
			'globalsReference' => '__globals__ maps a color or typography setting to a site global, e.g. {"__globals__":{"title_color":"globals/colors?id=primary"}}. See elementor-get-globals.',
		);
	}
}
