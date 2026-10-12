<?php
/**
 * Element catalog read from the live Elementor registry.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps Elementor's elements and widgets managers so tools only ever offer
 * widgets and settings that exist on this site, including widgets from Pro
 * and other add-ons.
 *
 * Controls are read through get_controls(). In REST requests Elementor
 * registers every control, style controls included (Performance::should_optimize_controls()
 * is false when REST_REQUEST is defined).
 */
final class ElementorCatalog implements Catalog {

	/** Control types that only decorate the panel and hold no value. */
	private const NON_VALUE_TYPES = array( 'section', 'tab', 'tabs', 'heading', 'divider', 'raw_html', 'button', 'deprecated_notice', 'alert', 'notice', 'wp_widget' );

	/** Widgets that exist only to share controls with other widgets. */
	private const INTERNAL_WIDGETS = array( 'common', 'common-base', 'common-optimized' );

	/** Short descriptions of how each control type's value is shaped. */
	public const VALUE_SHAPES = array(
		'text'             => 'string',
		'textarea'         => 'string',
		'wysiwyg'          => 'HTML string, e.g. "<p>Text</p>"',
		'code'             => 'string',
		'number'           => 'number',
		'select'           => 'one option key as a string',
		'select2'          => 'option key as a string (array of keys if multiple)',
		'choose'           => 'one option key as a string, e.g. "left"',
		'switcher'         => '"yes" to switch on, "" to switch off',
		'popover_toggle'   => '"custom" (or "yes") to open the group, "" to reset',
		'color'            => 'CSS color string, e.g. "#5B8A2F" or "rgba(0,0,0,0.5)"; or reference a global with __globals__',
		'slider'           => '{"unit":"px","size":24,"sizes":[]}',
		'dimensions'       => '{"unit":"px","top":"20","right":"20","bottom":"20","left":"20","isLinked":false}',
		'gaps'             => '{"unit":"px","column":"24","row":"24","isLinked":true}',
		'media'            => '{"url":"https://…/image.jpg","id":123} (id from list-media; use "" for an external URL)',
		'url'              => '{"url":"https://…","is_external":"","nofollow":""}',
		'icons'            => '{"value":"fas fa-arrow-right","library":"fa-solid"} (Font Awesome 5 class names; library fa-solid, fa-regular or fa-brands)',
		'icon'             => 'Font Awesome class string, e.g. "fa fa-star"',
		'gallery'          => 'array of {"id":123,"url":"https://…"}',
		'repeater'         => 'array of row objects; give each row a unique "_id" (7 hex chars) plus the fields listed under "fields"',
		'font'             => 'Google or system font family name, e.g. "Outfit"',
		'box_shadow'       => '{"horizontal":0,"vertical":12,"blur":30,"spread":-8,"color":"rgba(0,0,0,0.15)"}',
		'text_shadow'      => '{"horizontal":0,"vertical":2,"blur":8,"color":"rgba(0,0,0,0.3)"}',
		'image_dimensions' => '{"width":"","height":""}',
		'date_time'        => 'string "YYYY-mm-dd HH:ii"',
		'hidden'           => 'string',
	);

	/**
	 * How group controls work. Elementor expands each group into a toggle key
	 * plus prefixed sub-keys, so the toggle must be set for the sub-keys to apply.
	 */
	public const GROUP_HINTS = array(
		'typography' => 'Set "<prefix>_typography":"custom" and then e.g. "<prefix>_font_family":"Outfit", "<prefix>_font_size":{"unit":"px","size":48}, "<prefix>_font_weight":"600", "<prefix>_line_height":{"unit":"em","size":1.1}.',
		'background' => 'Set "<prefix>_background":"classic" and then "<prefix>_color":"#F4F5F1", or "<prefix>_image":{"url":"…","id":""} with "<prefix>_size":"cover" and "<prefix>_position":"center center". Use "gradient" for gradients.',
		'border'     => 'Set "<prefix>_border":"solid" and then "<prefix>_width" (dimensions) and "<prefix>_color". Corner radius is a separate "border_radius" dimensions key.',
		'box-shadow' => 'Set "<prefix>_box_shadow_type":"yes" and then "<prefix>_box_shadow":{"horizontal":0,"vertical":12,"blur":30,"spread":-8,"color":"rgba(0,0,0,0.15)"}.',
		'css-filter' => 'Set "<prefix>_css_filter":"custom" and then e.g. "<prefix>_blur" or "<prefix>_brightness" sliders.',
	);

	/** Field name of the switch that turns each group control on. */
	private const GROUP_SWITCHES = array(
		'typography' => 'typography',
		'background' => 'background',
		'border'     => 'border',
		'box-shadow' => 'box_shadow_type',
		'css-filter' => 'css_filter',
	);

	/** @var array<string, array<string, array<string, mixed>>> type key => controls */
	private array $controls = array();

	public static function available(): bool {
		return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->widgets_manager, \Elementor\Plugin::$instance->elements_manager );
	}

	public function exists( string $el_type, ?string $widget_type ): bool {
		return null !== $this->type( $el_type, $widget_type );
	}

	public function accepts_children( string $el_type, ?string $widget_type ): bool {
		if ( 'widget' !== $el_type ) {
			return $this->exists( $el_type, null );
		}
		$widget = $this->type( 'widget', $widget_type );
		return $widget instanceof \Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
	}

	public function setting_keys( string $el_type, ?string $widget_type ): array {
		return $this->keys_of( $this->controls( $el_type, $widget_type ) );
	}

	/**
	 * Valid page settings keys of a document (template, hide_title, background…).
	 *
	 * @param \Elementor\Core\Base\Document $document Document.
	 * @return list<string>
	 */
	public function document_setting_keys( $document ): array {
		return $this->keys_of( self::value_controls( $document ) );
	}

	/**
	 * Control names plus their responsive variants.
	 *
	 * @param array<string, array<string, mixed>> $controls Value controls.
	 * @return list<string>
	 */
	private function keys_of( array $controls ): array {
		$devices = $this->devices();
		$keys    = array();
		foreach ( $controls as $name => $control ) {
			$keys[] = (string) $name;
			if ( self::is_responsive( $control ) ) {
				foreach ( $devices as $device ) {
					$keys[] = $name . '_' . $device;
				}
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Every panel widget with title, categories and whether it needs Pro.
	 *
	 * @return list<array{name: string, title: string, categories: list<string>, pro: bool, nestable: bool}>
	 */
	public function widgets(): array {
		$list = array();
		foreach ( (array) \Elementor\Plugin::$instance->widgets_manager->get_widget_types() as $name => $widget ) {
			$name = (string) $name;
			if ( ! $this->usable_widget( $name, $widget ) || ! $widget->show_in_panel() ) {
				continue;
			}
			$list[] = array(
				'name'       => $name,
				'title'      => wp_strip_all_tags( (string) $widget->get_title() ),
				'categories' => array_values( array_map( 'strval', (array) $widget->get_categories() ) ),
				'pro'        => str_starts_with( get_class( $widget ), 'ElementorPro\\' ),
				'nestable'   => $widget instanceof \Elementor\Modules\NestedElements\Base\Widget_Nested_Base,
			);
		}
		return $list;
	}

	/**
	 * Layout element types available here: container (flexbox/grid) and/or section + column.
	 *
	 * @return list<string>
	 */
	public function layout_types(): array {
		return array_values( array_filter( array( 'container', 'section', 'column' ), fn( string $type ): bool => $this->exists( $type, null ) ) );
	}

	/**
	 * Breakpoint suffixes other than desktop, e.g. ["mobile", "tablet"].
	 *
	 * @return list<string>
	 */
	public function devices(): array {
		// VERIFY: Breakpoints manager get_active_devices_list() (Elementor 4.3.4 core/breakpoints/manager.php).
		$devices = \Elementor\Plugin::$instance->breakpoints->get_active_devices_list( array( 'add_desktop' => false ) );
		return array_values( array_map( 'strval', (array) $devices ) );
	}

	/**
	 * Compact control descriptions for one element type.
	 *
	 * @return array{controls: list<array<string, mixed>>, groups: array<string, string>}
	 */
	public function schema( string $el_type, ?string $widget_type ): array {
		$controls = array();
		$groups   = array();
		foreach ( $this->controls( $el_type, $widget_type ) as $name => $control ) {
			$controls[] = self::describe_control( (string) $name, $control );

			$prefix = self::group_prefix( (string) $name, $control );
			if ( null !== $prefix ) {
				$groups[ $prefix[1] . '_*' ] = str_replace( '<prefix>', $prefix[1], self::GROUP_HINTS[ $prefix[0] ] );
			}
		}
		return array(
			'controls' => $controls,
			'groups'   => $groups,
		);
	}

	/**
	 * @param array<string, mixed> $control Elementor control.
	 * @return array<string, mixed>
	 */
	private static function describe_control( string $name, array $control ): array {
		$type  = (string) ( $control['type'] ?? 'text' );
		$entry = array(
			'key'   => $name,
			'type'  => $type,
			'label' => wp_strip_all_tags( (string) ( $control['label'] ?? '' ) ),
			'tab'   => (string) ( $control['tab'] ?? 'content' ),
		);
		if ( ! empty( $control['section'] ) ) {
			$entry['section'] = (string) $control['section'];
		}
		if ( isset( self::VALUE_SHAPES[ $type ] ) ) {
			$entry['value'] = self::VALUE_SHAPES[ $type ];
		}
		if ( ! empty( $control['options'] ) && is_array( $control['options'] ) ) {
			$entry['options'] = array_slice( array_map( 'strval', array_keys( $control['options'] ) ), 0, 40 );
		}
		if ( isset( $control['default'] ) && ( is_scalar( $control['default'] ) || ( is_array( $control['default'] ) && count( $control['default'] ) <= 6 ) ) && '' !== $control['default'] && array() !== $control['default'] ) {
			$entry['default'] = $control['default'];
		}
		if ( ! empty( $control['size_units'] ) && is_array( $control['size_units'] ) ) {
			$entry['units'] = array_values( array_map( 'strval', $control['size_units'] ) );
		}
		if ( self::is_responsive( $control ) ) {
			$entry['responsive'] = true;
		}
		if ( ! empty( $control['condition'] ) && is_array( $control['condition'] ) ) {
			$entry['showsWhen'] = $control['condition'];
		}
		if ( 'repeater' === $type && ! empty( $control['fields'] ) && is_array( $control['fields'] ) ) {
			$entry['fields'] = array_values(
				array_map(
					static fn( array $field ): array => self::describe_control( (string) ( $field['name'] ?? '' ), $field ),
					array_filter( $control['fields'], static fn( $field ): bool => is_array( $field ) && ! empty( $field['name'] ) && ! in_array( $field['type'] ?? '', self::NON_VALUE_TYPES, true ) )
				)
			);
		}
		return $entry;
	}

	/**
	 * Group type and key prefix when this control is the switch of a group
	 * control (e.g. "button_background_background" → background, "button_background").
	 * Elementor tags every group field with the CSS class elementor-group-control-{type}.
	 *
	 * @param array<string, mixed> $control Elementor control.
	 * @return array{0: string, 1: string}|null
	 */
	private static function group_prefix( string $name, array $control ): ?array {
		// VERIFY: Group_Control_Base::get_base_group_classes() class format (Elementor 4.3.4 includes/controls/groups/base.php).
		if ( ! preg_match( '/elementor-group-control-([a-z-]+) elementor-group-control\b/', (string) ( $control['classes'] ?? '' ), $match ) ) {
			return null;
		}
		$type = $match[1];
		if ( ! isset( self::GROUP_SWITCHES[ $type ] ) ) {
			return null;
		}
		$suffix = '_' . self::GROUP_SWITCHES[ $type ];
		return str_ends_with( $name, $suffix ) && strlen( $name ) > strlen( $suffix ) ? array( $type, substr( $name, 0, -strlen( $suffix ) ) ) : null;
	}

	/**
	 * Responsive controls are either registered once with is_responsive (new
	 * breakpoints mode, device keys added by the editor) or once per device
	 * with a "responsive" arg on each copy. Only the base key needs suffixes.
	 *
	 * @param array<string, mixed> $control Elementor control.
	 */
	private static function is_responsive( array $control ): bool {
		return ! empty( $control['is_responsive'] ) || ( ! empty( $control['responsive'] ) && empty( $control['parent'] ) );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function controls( string $el_type, ?string $widget_type ): array {
		$key = $el_type . '/' . (string) $widget_type;
		if ( isset( $this->controls[ $key ] ) ) {
			return $this->controls[ $key ];
		}
		$type                   = $this->type( $el_type, $widget_type );
		$this->controls[ $key ] = $type ? self::value_controls( $type ) : array();
		return $this->controls[ $key ];
	}

	/**
	 * Controls of a stack that hold a value.
	 *
	 * @param \Elementor\Controls_Stack $stack Element, widget or document.
	 * @return array<string, array<string, mixed>>
	 */
	private static function value_controls( $stack ): array {
		$controls = array();
		foreach ( (array) $stack->get_controls() as $name => $control ) {
			if ( is_array( $control ) && ! in_array( $control['type'] ?? '', self::NON_VALUE_TYPES, true ) ) {
				$controls[ (string) $name ] = $control;
			}
		}
		return $controls;
	}

	/**
	 * Registered type object, or null when it does not exist here.
	 *
	 * @return \Elementor\Element_Base|null
	 */
	private function type( string $el_type, ?string $widget_type ) {
		if ( str_starts_with( $el_type, 'e-' ) ) {
			return null;
		}
		if ( 'widget' === $el_type ) {
			if ( null === $widget_type ) {
				return null;
			}
			$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
			return $this->usable_widget( $widget_type, $widget ) ? $widget : null;
		}
		$element = \Elementor\Plugin::$instance->elements_manager->get_element_types( $el_type );
		return $element instanceof \Elementor\Element_Base ? $element : null;
	}

	/**
	 * @param mixed $widget Registered widget object.
	 */
	private function usable_widget( string $name, $widget ): bool {
		return $widget instanceof \Elementor\Widget_Base
			&& ! in_array( $name, self::INTERNAL_WIDGETS, true )
			&& ! str_starts_with( $name, 'e-' );
	}
}
