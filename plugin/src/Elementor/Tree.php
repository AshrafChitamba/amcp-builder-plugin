<?php
/**
 * Elementor element tree operations.
 *
 * @package AcMcp
 */

namespace AcMcp\Elementor;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use AcMcp\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Edits an Elementor document: a nested list of elements, each
 * {id, elType, widgetType?, settings, elements, isInner}.
 *
 * Pure PHP with no WordPress calls, so every operation is unit tested.
 */
final class Tree {

	/** @var list<array<string, mixed>> */
	private array $elements;

	/**
	 * @param array<int, mixed> $elements Stored elements (_elementor_data, decoded).
	 */
	public function __construct( array $elements ) {
		$this->elements = self::normalize( $elements );
	}

	/**
	 * Nested elements in document order.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function to_array(): array {
		return $this->elements;
	}

	/**
	 * Every element, flattened depth first, with child IDs instead of children.
	 * Used to compute diffs.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function flat(): array {
		$result = array();
		self::walk(
			$this->elements,
			static function ( array $element ) use ( &$result ): void {
				$element['children'] = array_column( $element['elements'], 'id' );
				unset( $element['elements'] );
				$result[] = $element;
			}
		);
		return $result;
	}

	/**
	 * All element IDs, depth first.
	 *
	 * @return list<string>
	 */
	public function ids(): array {
		return array_column( $this->flat(), 'id' );
	}

	public function count(): int {
		return count( $this->ids() );
	}

	public function has( string $id ): bool {
		return null !== self::find_path( $this->elements, $id );
	}

	/**
	 * @return array<string, mixed> The element with its children.
	 */
	public function get( string $id ): array {
		$path = $this->path( $id );
		$list = $this->elements;
		foreach ( array_slice( $path, 0, -1 ) as $index ) {
			$list = $list[ $index ]['elements'];
		}
		return $list[ end( $path ) ];
	}

	/**
	 * ID of the element's parent, or null when it sits at the top level.
	 */
	public function parent_id( string $id ): ?string {
		$path = $this->path( $id );
		if ( 1 === count( $path ) ) {
			return null;
		}
		$list = $this->elements;
		$node = null;
		foreach ( array_slice( $path, 0, -1 ) as $index ) {
			$node = $list[ $index ];
			$list = $node['elements'];
		}
		return $node['id'];
	}

	/**
	 * Ordered child IDs. Null means the top level.
	 *
	 * @return list<string>
	 */
	public function children( ?string $parent ): array {
		$list = null === $parent ? $this->elements : $this->get( $parent )['elements'];
		return array_column( $list, 'id' );
	}

	/**
	 * All descendant IDs of an element, depth first.
	 *
	 * @return list<string>
	 */
	public function descendants( string $id ): array {
		$result = array();
		self::walk(
			$this->get( $id )['elements'],
			static function ( array $element ) use ( &$result ): void {
				$result[] = $element['id'];
			}
		);
		return $result;
	}

	/**
	 * Nested view for reading.
	 *
	 * @param string|null $root      Start element, or null for the whole document.
	 * @param int         $max_depth Levels to include; deeper levels only report a count.
	 * @return list<array<string, mixed>>
	 */
	public function outline( ?string $root = null, int $max_depth = 50 ): array {
		$list = null === $root ? $this->elements : array( $this->get( $root ) );
		return array_map( static fn( array $element ): array => self::node( $element, 1, $max_depth ), $list );
	}

	/**
	 * What the top level is built from: containers, sections, atomic, mixed or empty.
	 */
	public function structure(): string {
		$kinds = array();
		foreach ( $this->elements as $element ) {
			if ( self::is_atomic( $element ) ) {
				$kinds['atomic'] = true;
			} elseif ( 'section' === $element['elType'] ) {
				$kinds['sections'] = true;
			} else {
				$kinds['containers'] = true;
			}
		}
		if ( ! $kinds ) {
			return 'empty';
		}
		return 1 === count( $kinds ) ? (string) array_key_first( $kinds ) : 'mixed';
	}

	/**
	 * Adds new elements built from nested specs.
	 *
	 * @param list<array<string, mixed>> $specs    Nested specs: elType, widgetType?, settings?, elements?.
	 * @param string|null                $parent   Element ID, or null for the top level.
	 * @param int|null                   $position Index among the parent's children; null appends.
	 * @return list<string> IDs of every created element, depth first.
	 */
	public function insert( array $specs, ?string $parent, ?int $position, Ids $ids ): array {
		$parent_el   = null === $parent ? null : $this->get( $parent );
		$parent_type = null === $parent_el ? null : (string) $parent_el['elType'];
		$created     = array();
		$built       = array();
		foreach ( $specs as $spec ) {
			$built[] = self::build( $spec, $parent_type, ! empty( $parent_el['isInner'] ), $ids, $created );
		}

		$list = &$this->children_ref( $parent );
		$at   = null === $position ? count( $list ) : max( 0, min( $position, count( $list ) ) );
		array_splice( $list, $at, 0, $built );
		return $created;
	}

	/**
	 * Removes an element and everything inside it.
	 *
	 * @return list<string> Removed IDs.
	 */
	public function remove( string $id ): array {
		$removed = array_merge( array( $id ), $this->descendants( $id ) );
		$this->detach( $id );
		return $removed;
	}

	/**
	 * Moves an element (with its children) to a new parent and/or position.
	 *
	 * @param string|null $new_parent Element ID, or null for the top level.
	 */
	public function move( string $id, ?string $new_parent, ?int $position ): void {
		$this->get( $id );
		if ( null !== $new_parent ) {
			$this->get( $new_parent );
			if ( $new_parent === $id || in_array( $new_parent, $this->descendants( $id ), true ) ) {
				throw new TreeError( 'invalid_move', 'An element cannot be moved inside itself.', 'Choose a parent outside the element you are moving.' );
			}
		}

		$parent_el = null === $new_parent ? null : $this->get( $new_parent );
		$element   = self::with_inner( $this->detach( $id ), null === $parent_el ? null : (string) $parent_el['elType'], ! empty( $parent_el['isInner'] ) );

		$list = &$this->children_ref( $new_parent );
		$at   = null === $position ? count( $list ) : max( 0, min( $position, count( $list ) ) );
		array_splice( $list, $at, 0, array( $element ) );
	}

	/**
	 * Copies an element and its children, placing the copy right after it.
	 *
	 * @return array{id: string, created: list<string>}
	 */
	public function duplicate( string $id, Ids $ids ): array {
		$parent   = $this->parent_id( $id );
		$siblings = $this->children( $parent );
		$created  = array();
		$copy     = self::reid( $this->get( $id ), $ids, $created );

		$list = &$this->children_ref( $parent );
		array_splice( $list, (int) array_search( $id, $siblings, true ) + 1, 0, array( $copy ) );

		return array(
			'id'      => $copy['id'],
			'created' => $created,
		);
	}

	/**
	 * Changes settings. In merge mode, a null value removes that key.
	 *
	 * @param array<string, mixed> $settings Settings patch.
	 * @param string               $mode     "merge" or "replace".
	 */
	public function update( string $id, array $settings, string $mode = 'merge' ): void {
		$element = &$this->element_ref( $id );
		$current = 'replace' === $mode ? array() : $element['settings'];
		foreach ( $settings as $key => $value ) {
			if ( null === $value ) {
				unset( $current[ $key ] );
			} elseif ( '__globals__' === $key && is_array( $value ) && 'merge' === $mode ) {
				// Merge global references one level deep so one call can set or clear a single reference.
				$globals                = array_merge( (array) ( $current['__globals__'] ?? array() ), $value );
				$current['__globals__'] = array_filter( $globals, static fn( $ref ): bool => null !== $ref );
			} else {
				$current[ $key ] = $value;
			}
		}
		$element['settings'] = $current;
	}

	/**
	 * Structural problems in the stored data.
	 *
	 * @return list<string>
	 */
	public function integrity(): array {
		$seen   = array();
		$errors = array();
		foreach ( $this->ids() as $id ) {
			if ( isset( $seen[ $id ] ) ) {
				$errors[] = sprintf( 'Element ID %s is used more than once.', $id );
			}
			$seen[ $id ] = true;
		}
		return $errors;
	}

	/**
	 * True for Elementor v4 Atomic elements, which AC MCP for Bricks and Elementor only reads.
	 *
	 * @param array<string, mixed> $element Stored element.
	 */
	public static function is_atomic( array $element ): bool {
		// VERIFY: Atomic elements use "e-" prefixed types (e-flexbox, e-div-block, e-heading…) and a styles map (Elementor 4.3.4).
		return str_starts_with( (string) $element['elType'], 'e-' )
			|| str_starts_with( (string) ( $element['widgetType'] ?? '' ), 'e-' )
			|| ! empty( $element['styles'] );
	}

	/**
	 * @param array<string, mixed> $spec        Element spec.
	 * @param string|null          $parent_type  elType of the parent, null for the top level.
	 * @param bool                 $parent_inner Whether the parent is an inner element.
	 * @param list<string>         $created      Collects created IDs.
	 * @return array<string, mixed>
	 */
	private static function build( array $spec, ?string $parent_type, bool $parent_inner, Ids $ids, array &$created ): array {
		$id        = $ids->next();
		$created[] = $id;
		$el_type   = (string) $spec['elType'];

		$element = array(
			'id'       => $id,
			'elType'   => $el_type,
			'settings' => is_array( $spec['settings'] ?? null ) ? $spec['settings'] : array(),
			'elements' => array(),
			'isInner'  => self::is_inner( $el_type, $parent_type, $parent_inner ),
		);
		if ( 'widget' === $el_type ) {
			$element['widgetType'] = (string) $spec['widgetType'];
		}

		foreach ( (array) ( $spec['elements'] ?? array() ) as $child ) {
			$element['elements'][] = self::build( $child, $el_type, $element['isInner'], $ids, $created );
		}
		return $element;
	}

	/**
	 * Deep copy with fresh IDs.
	 *
	 * @param array<string, mixed> $element Element to copy.
	 * @param list<string>         $created Collects created IDs.
	 * @return array<string, mixed>
	 */
	private static function reid( array $element, Ids $ids, array &$created ): array {
		$element['id'] = $ids->next();
		$created[]     = $element['id'];
		foreach ( $element['elements'] as $index => $child ) {
			$element['elements'][ $index ] = self::reid( $child, $ids, $created );
		}
		return $element;
	}

	/**
	 * Takes an element out of the tree and returns it.
	 *
	 * @return array<string, mixed>
	 */
	private function detach( string $id ): array {
		$parent  = $this->parent_id( $id );
		$list    = &$this->children_ref( $parent );
		$index   = (int) array_search( $id, array_column( $list, 'id' ), true );
		$element = $list[ $index ];
		array_splice( $list, $index, 1 );
		return $element;
	}

	/**
	 * @return list<int> Indexes from the top level down to the element.
	 */
	private function path( string $id ): array {
		$path = self::find_path( $this->elements, $id );
		if ( null === $path ) {
			throw new TreeError( 'element_not_found', sprintf( 'Element "%s" does not exist on this page.', $id ), 'Call elementor-get-page to see the element IDs on the page.' );
		}
		return $path;
	}

	/**
	 * Reference to the children list of an element, or to the top level.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function &children_ref( ?string $parent ): array {
		if ( null === $parent ) {
			return $this->elements;
		}
		$element = &$this->element_ref( $parent );
		return $element['elements'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function &element_ref( string $id ): array {
		$path = $this->path( $id );
		$list = &$this->elements;
		$last = array_pop( $path );
		foreach ( $path as $index ) {
			$list = &$list[ $index ]['elements'];
		}
		return $list[ $last ];
	}

	/**
	 * @param list<array<string, mixed>> $list Elements to search.
	 * @return list<int>|null
	 */
	private static function find_path( array $list, string $id ): ?array {
		foreach ( $list as $index => $element ) {
			if ( $element['id'] === $id ) {
				return array( $index );
			}
			$sub = self::find_path( $element['elements'], $id );
			if ( null !== $sub ) {
				return array_merge( array( $index ), $sub );
			}
		}
		return null;
	}

	/**
	 * Calls $visit for every element, depth first.
	 *
	 * @param list<array<string, mixed>> $list Elements.
	 */
	private static function walk( array $list, callable $visit ): void {
		foreach ( $list as $element ) {
			$visit( $element );
			self::walk( $element['elements'], $visit );
		}
	}

	/**
	 * @param array<string, mixed> $element Element.
	 * @return array<string, mixed>
	 */
	private static function node( array $element, int $depth, int $max_depth ): array {
		$node = array(
			'id'     => $element['id'],
			'elType' => $element['elType'],
		);
		if ( isset( $element['widgetType'] ) ) {
			$node['widgetType'] = $element['widgetType'];
		}
		if ( ! empty( $element['isInner'] ) ) {
			$node['isInner'] = true;
		}
		if ( self::is_atomic( $element ) ) {
			$node['atomic'] = true;
		}
		$node['settings'] = (object) $element['settings'];

		if ( $depth >= $max_depth ) {
			$node['childCount'] = count( $element['elements'] );
		} else {
			$node['children'] = array_map( static fn( array $child ): array => self::node( $child, $depth + 1, $max_depth ), $element['elements'] );
		}
		return $node;
	}

	/**
	 * Drops malformed entries and gives every element the same shape.
	 *
	 * @param array<int, mixed> $list Raw elements.
	 * @return list<array<string, mixed>>
	 */
	private static function normalize( array $list ): array {
		$result = array();
		foreach ( $list as $element ) {
			if ( ! is_array( $element ) || empty( $element['id'] ) || empty( $element['elType'] ) ) {
				continue;
			}
			$element['id']       = (string) $element['id'];
			$element['settings'] = is_array( $element['settings'] ?? null ) ? $element['settings'] : array();
			$element['elements'] = self::normalize( is_array( $element['elements'] ?? null ) ? $element['elements'] : array() );
			$result[]            = $element;
		}
		return $result;
	}

	/**
	 * Elementor's isInner rule: top-level sections and containers and the
	 * columns of a top-level section are not inner; anything nested deeper is.
	 */
	private static function is_inner( string $el_type, ?string $parent_type, bool $parent_inner ): bool {
		if ( 'widget' === $el_type ) {
			return false;
		}
		if ( 'column' === $el_type ) {
			return $parent_inner;
		}
		return null !== $parent_type;
	}

	/**
	 * Recomputes isInner for a moved element and everything inside it.
	 *
	 * @param array<string, mixed> $element Element.
	 * @return array<string, mixed>
	 */
	private static function with_inner( array $element, ?string $parent_type, bool $parent_inner ): array {
		if ( 'widget' !== $element['elType'] ) {
			$element['isInner'] = self::is_inner( (string) $element['elType'], $parent_type, $parent_inner );
		}
		foreach ( (array) ( $element['elements'] ?? array() ) as $index => $child ) {
			$element['elements'][ $index ] = self::with_inner( $child, (string) $element['elType'], ! empty( $element['isInner'] ) );
		}
		return $element;
	}
}
