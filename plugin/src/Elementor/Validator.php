<?php
/**
 * Validates Elementor element specs and settings before they are saved.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Elementor;

use McpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Rejects unknown widgets, unknown settings keys, Atomic elements and invalid
 * nesting. Elementor silently drops unregistered widgets on save, so checking
 * first is the only way the AI learns about the mistake.
 */
final class Validator {

	public const MAX_ELEMENTS = 200;
	public const MAX_DEPTH    = 12;

	/** Settings every element accepts that are not regular controls. */
	public const META_KEYS = array( '__globals__', '__dynamic__', '_title' );

	/** Which child elTypes each parent elType accepts ("root" = top level). */
	private const NESTING = array(
		'root'      => array( 'container', 'section' ),
		'container' => array( 'container', 'widget' ),
		'section'   => array( 'column' ),
		'column'    => array( 'widget', 'section' ),
		'widget'    => array( 'container' ),
	);

	public function __construct( private Catalog $catalog ) {}

	/**
	 * @param mixed                     $specs  Nested specs from the caller.
	 * @param array<string, mixed>|null $parent Parent element, null for the top level.
	 */
	public function specs( $specs, ?array $parent ): void {
		if ( null !== $parent ) {
			$this->assert_not_atomic( $parent );
		}
		if ( ! is_array( $specs ) || ! array_is_list( $specs ) || ! $specs ) {
			throw new TreeError( 'invalid_elements', 'elements must be a non-empty array of element objects.', 'Pass e.g. [{"elType":"container","elements":[{"elType":"widget","widgetType":"heading","settings":{"title":"Hello"}}]}].' );
		}

		$count = 0;
		foreach ( $specs as $spec ) {
			$this->spec( $spec, $parent, 1, $count );
		}
		if ( $count > self::MAX_ELEMENTS ) {
			throw new TreeError( 'too_many_elements', sprintf( 'At most %d elements can be added in one call.', self::MAX_ELEMENTS ), 'Split the layout into several calls, one section at a time.' );
		}
	}

	/**
	 * Refuses changes to Atomic (v4) elements, which MCP Builder only reads.
	 *
	 * @param array<string, mixed> $element Existing element.
	 */
	public function editable( array $element ): void {
		$this->assert_not_atomic( $element );
	}

	/**
	 * Checks that an existing element may be placed under a new parent.
	 *
	 * @param array<string, mixed>      $element Element being moved.
	 * @param array<string, mixed>|null $parent  New parent, null for the top level.
	 */
	public function placement( array $element, ?array $parent ): void {
		$this->assert_not_atomic( $element );
		if ( null !== $parent ) {
			$this->assert_not_atomic( $parent );
		}
		$this->assert_nesting( (string) $element['elType'], $parent );
	}

	/**
	 * @param mixed $settings Settings object.
	 */
	public function settings( string $el_type, ?string $widget_type, $settings ): void {
		$what = self::describe( $el_type, $widget_type );
		if ( ! is_array( $settings ) || ( $settings && array_is_list( $settings ) ) ) {
			throw new TreeError( 'invalid_settings', sprintf( 'Settings for %s must be an object of key/value pairs.', $what ), 'Pass e.g. {"title":"Hello","header_size":"h2"}.' );
		}

		$known = array_merge( $this->catalog->setting_keys( $el_type, $widget_type ), self::META_KEYS );
		foreach ( array_keys( $settings ) as $key ) {
			$this->assert_known( (string) $key, $known, $el_type, $widget_type );
		}

		if ( isset( $settings['__globals__'] ) ) {
			if ( ! is_array( $settings['__globals__'] ) || array_is_list( $settings['__globals__'] ) ) {
				throw new TreeError( 'invalid_globals', '__globals__ must be an object mapping a setting key to a global reference.', 'Pass e.g. {"__globals__":{"title_color":"globals/colors?id=primary"}}. Call elementor-get-globals for the IDs.' );
			}
			foreach ( $settings['__globals__'] as $key => $reference ) {
				$this->assert_known( (string) $key, $known, $el_type, $widget_type );
				if ( null !== $reference && '' !== $reference && ( ! is_string( $reference ) || ! preg_match( '#^globals/(colors|typography)\?id=[A-Za-z0-9_-]+$#', $reference ) ) ) {
					throw new TreeError( 'invalid_globals', sprintf( 'Global reference for "%s" must look like "globals/colors?id=primary" or "globals/typography?id=accent".', $key ), 'Call elementor-get-globals for the available IDs.' );
				}
			}
		}
	}

	/**
	 * @param mixed                     $spec   Element spec.
	 * @param array<string, mixed>|null $parent Parent element or spec.
	 */
	private function spec( $spec, ?array $parent, int $depth, int &$count ): void {
		++$count;
		if ( $depth > self::MAX_DEPTH ) {
			throw new TreeError( 'too_deep', sprintf( 'Elements can be nested at most %d levels deep in one call.', self::MAX_DEPTH ), 'Add deeper content in a second call.' );
		}
		if ( ! is_array( $spec ) || empty( $spec['elType'] ) || ! is_string( $spec['elType'] ) ) {
			throw new TreeError( 'invalid_element', 'Every element needs an "elType": "container" for layout or "widget" for content.', 'Pass e.g. {"elType":"widget","widgetType":"heading","settings":{"title":"Hello"}}.' );
		}

		$el_type     = $spec['elType'];
		$widget_type = null;
		if ( 'widget' === $el_type ) {
			if ( empty( $spec['widgetType'] ) || ! is_string( $spec['widgetType'] ) ) {
				throw new TreeError( 'invalid_element', 'A widget needs a "widgetType", e.g. "heading", "text-editor", "image" or "button".', 'Call elementor-list-widgets to see the widget names on this site.' );
			}
			$widget_type = $spec['widgetType'];
		}

		$this->assert_not_atomic( $spec );
		if ( ! $this->catalog->exists( $el_type, $widget_type ) ) {
			throw new TreeError(
				'widget' === $el_type ? 'unknown_widget' : 'unknown_element',
				sprintf( 'Unknown %s.', self::describe( $el_type, $widget_type ) ),
				'widget' === $el_type ? 'Call elementor-list-widgets to see the widget names on this site.' : 'Use "container" for layout and "widget" for content.'
			);
		}
		$this->assert_nesting( $el_type, $parent );
		$this->settings( $el_type, $widget_type, $spec['settings'] ?? array() );

		$children = $spec['elements'] ?? array();
		if ( ! is_array( $children ) || ( $children && ! array_is_list( $children ) ) ) {
			throw new TreeError( 'invalid_children', sprintf( 'elements of %s must be an array of elements.', self::describe( $el_type, $widget_type ) ) );
		}
		foreach ( $children as $child ) {
			$this->spec( $child, $spec, $depth + 1, $count );
		}
	}

	/**
	 * @param array<string, mixed>|null $parent Parent element or spec, null for the top level.
	 */
	private function assert_nesting( string $el_type, ?array $parent ): void {
		$parent_type   = null === $parent ? 'root' : (string) $parent['elType'];
		$parent_widget = null === $parent ? null : ( $parent['widgetType'] ?? null );

		if ( 'widget' === $parent_type && ! $this->catalog->accepts_children( 'widget', $parent_widget ) ) {
			throw new TreeError( 'not_nestable', sprintf( 'A "%s" widget cannot contain other elements.', (string) $parent_widget ), 'Add the elements to a container instead.' );
		}

		$allowed = self::NESTING[ $parent_type ] ?? array();
		if ( in_array( $el_type, $allowed, true ) ) {
			return;
		}

		$fix = match ( true ) {
			'root' === $parent_type && 'widget' === $el_type => 'Widgets must sit inside a container. Wrap them: {"elType":"container","elements":[…your widgets…]}.',
			'section' === $parent_type                       => 'Sections only hold columns. Add widgets to one of its columns, or use containers for new layouts.',
			default                                          => sprintf( 'A %s can hold: %s.', 'root' === $parent_type ? 'page top level' : $parent_type, implode( ', ', $allowed ) ),
		};
		throw new TreeError(
			'invalid_nesting',
			sprintf( 'A "%s" cannot be placed %s.', $el_type, 'root' === $parent_type ? 'at the top level of the page' : sprintf( 'inside a "%s"', $parent_type ) ),
			$fix
		);
	}

	/**
	 * @param array<string, mixed> $element Element or spec.
	 */
	private function assert_not_atomic( array $element ): void {
		if ( Tree::is_atomic( $element + array( 'elType' => '' ) ) ) {
			throw new TreeError(
				'atomic_not_supported',
				'This is an Elementor v4 Atomic element. MCP Builder can read Atomic content but not edit it.',
				'Use classic containers and widgets, or edit Atomic elements with Elementor\'s own tools.'
			);
		}
	}

	/**
	 * Rejects keys that are not in $known, suggesting close matches.
	 *
	 * @param array<string, mixed> $settings Settings to check.
	 * @param list<string>         $known    Valid keys.
	 * @param string               $subject  What the settings belong to, e.g. "page settings".
	 * @param string               $fix      What to do instead.
	 */
	public static function known_keys( array $settings, array $known, string $subject, string $fix ): void {
		foreach ( array_keys( $settings ) as $key ) {
			$key = (string) $key;
			if ( in_array( $key, $known, true ) ) {
				continue;
			}
			$close = self::suggest( $key, $known );
			throw new TreeError( 'unknown_setting', sprintf( 'Unknown setting "%s" for %s.', $key, $subject ) . ( $close ? ' Close matches: ' . implode( ', ', $close ) . '.' : '' ), $fix );
		}
	}

	/**
	 * @param list<string> $known Valid keys.
	 */
	private function assert_known( string $key, array $known, string $el_type, ?string $widget_type ): void {
		if ( in_array( $key, $known, true ) ) {
			return;
		}
		$close = self::suggest( $key, $known );
		$what  = self::describe( $el_type, $widget_type );
		throw new TreeError(
			'unknown_setting',
			sprintf( 'Unknown setting "%s" for %s.', $key, $what ) . ( $close ? ' Close matches: ' . implode( ', ', $close ) . '.' : '' ),
			sprintf(
				'Call elementor-get-widget-schema with %s and only use keys it lists. Responsive keys add a device suffix, e.g. "padding_mobile".',
				'widget' === $el_type ? sprintf( 'widget "%s"', (string) $widget_type ) : sprintf( 'widget "%s"', $el_type )
			)
		);
	}

	private static function describe( string $el_type, ?string $widget_type ): string {
		return 'widget' === $el_type ? sprintf( 'widget "%s"', (string) $widget_type ) : sprintf( 'element type "%s"', $el_type );
	}

	/**
	 * Up to five known keys that look like the unknown one.
	 *
	 * @param list<string> $known Valid keys.
	 * @return list<string>
	 */
	private static function suggest( string $key, array $known ): array {
		$needle = strtolower( ltrim( $key, '_' ) );
		$scored = array();
		foreach ( array_unique( $known ) as $candidate ) {
			$plain    = strtolower( ltrim( $candidate, '_' ) );
			$distance = levenshtein( $needle, $plain );
			if ( $distance <= 3 || ( strlen( $needle ) > 3 && ( str_contains( $plain, $needle ) || str_contains( $needle, $plain ) ) ) ) {
				$scored[ $candidate ] = $distance;
			}
		}
		asort( $scored );
		return array_slice( array_keys( $scored ), 0, 5 );
	}
}
