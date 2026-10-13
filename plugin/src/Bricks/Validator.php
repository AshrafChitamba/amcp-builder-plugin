<?php
/**
 * Validates element specs and settings before they are saved.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Bricks;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use McpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Rejects unknown element names, unknown settings keys and invalid nesting,
 * so the AI never saves data Bricks would silently ignore.
 */
final class Validator {

	public const MAX_ELEMENTS = 200;
	public const MAX_DEPTH    = 12;

	/** Settings every element accepts that are not regular controls. */
	public const META_KEYS = array(
		'_cssGlobalClasses',
		'_cssId',
		'_cssClasses',
		'_cssCustom',
		'_attributes',
		'_conditions',
		'_interactions',
		'_hideElementBuilder',
		'_hideElementFrontend',
	);

	public function __construct( private Catalog $catalog, private bool $allow_custom_code = false ) {}

	/**
	 * @param mixed       $specs       Nested specs from the caller.
	 * @param string|null $parent_name Element name of the parent, null for root.
	 */
	public function specs( $specs, ?string $parent_name ): void {
		if ( null !== $parent_name && ! $this->catalog->nestable( $parent_name ) ) {
			throw new TreeError(
				'not_nestable',
				sprintf( 'A "%s" element cannot contain other elements.', $parent_name ),
				'Add the elements to a layout element instead: section, container, block or div.'
			);
		}
		if ( ! is_array( $specs ) || ! array_is_list( $specs ) || ! $specs ) {
			throw new TreeError( 'invalid_elements', 'elements must be a non-empty array of element objects.', 'Pass e.g. [{"name":"heading","settings":{"text":"Hello"}}].' );
		}

		$count = 0;
		foreach ( $specs as $spec ) {
			$this->spec( $spec, 1, $count );
		}
		if ( $count > self::MAX_ELEMENTS ) {
			throw new TreeError( 'too_many_elements', sprintf( 'At most %d elements can be added in one call.', self::MAX_ELEMENTS ), 'Split the layout into several calls, one section at a time.' );
		}
	}

	/**
	 * @param mixed $settings Settings object.
	 */
	public function settings( string $name, $settings ): void {
		if ( ! $this->allow_custom_code && is_array( $settings ) && ( 'code' === $name || ( 'svg' === $name && isset( $settings['code'] ) ) ) ) {
			throw \McpBuilder\Support\CustomCode::refusal( 'code' === $name ? 'A Code element' : 'An SVG element with inline code' );
		}
		if ( ! is_array( $settings ) || ( $settings && array_is_list( $settings ) ) ) {
			throw new TreeError( 'invalid_settings', sprintf( 'Settings for "%s" must be an object of key/value pairs.', $name ), 'Pass e.g. {"text":"Hello","tag":"h2"}.' );
		}

		$known = array_merge( $this->catalog->setting_keys( $name ), self::META_KEYS );
		foreach ( array_keys( $settings ) as $key ) {
			$base = explode( ':', (string) $key )[0];
			if ( in_array( $base, $known, true ) ) {
				continue;
			}
			$close = self::suggest( $base, $known );
			throw new TreeError(
				'unknown_setting',
				sprintf( 'Unknown setting "%s" for element "%s".', $key, $name ) . ( $close ? ' Close matches: ' . implode( ', ', $close ) . '.' : '' ),
				sprintf( 'Call bricks-get-element-schema with name "%s" and only use keys it lists. Responsive keys add a breakpoint suffix, e.g. "_padding:mobile_portrait".', $name )
			);
		}
	}

	/**
	 * @param mixed $spec Element spec.
	 */
	private function spec( $spec, int $depth, int &$count ): void {
		++$count;
		if ( $depth > self::MAX_DEPTH ) {
			throw new TreeError( 'too_deep', sprintf( 'Elements can be nested at most %d levels deep in one call.', self::MAX_DEPTH ), 'Add deeper content in a second call.' );
		}
		if ( ! is_array( $spec ) || empty( $spec['name'] ) || ! is_string( $spec['name'] ) ) {
			throw new TreeError( 'invalid_element', 'Every element needs a "name", e.g. "heading".', 'Call bricks-list-elements to see valid names.' );
		}

		$name = $spec['name'];
		if ( ! $this->catalog->has( $name ) ) {
			throw new TreeError( 'unknown_element', sprintf( 'Unknown element "%s".', $name ), 'Call bricks-list-elements to see the element names available on this site.' );
		}

		$this->settings( $name, $spec['settings'] ?? array() );

		$children = $spec['children'] ?? array();
		if ( ! is_array( $children ) || ( $children && ! array_is_list( $children ) ) ) {
			throw new TreeError( 'invalid_children', sprintf( 'children of "%s" must be an array of elements.', $name ) );
		}
		if ( $children && ! $this->catalog->nestable( $name ) ) {
			throw new TreeError( 'not_nestable', sprintf( 'A "%s" element cannot contain other elements.', $name ), 'Wrap the elements in a layout element instead: section, container, block or div.' );
		}
		foreach ( $children as $child ) {
			$this->spec( $child, $depth + 1, $count );
		}
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
			if ( $distance <= 3 || ( strlen( $needle ) > 2 && ( str_contains( $plain, $needle ) || str_contains( $needle, $plain ) ) ) ) {
				$scored[ $candidate ] = $distance;
			}
		}
		asort( $scored );
		return array_slice( array_keys( $scored ), 0, 5 );
	}
}
