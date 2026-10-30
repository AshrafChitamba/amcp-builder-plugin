<?php
/**
 * Bricks element tree operations.
 *
 * @package AcMcp
 */

namespace AcMcp\Bricks;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use AcMcp\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Edits a Bricks content area: a flat list of elements linked by `parent`
 * (0 for root elements) and ordered `children` ID lists. Root elements are
 * ordered by their position in the flat list.
 *
 * Pure PHP with no WordPress calls, so every operation is unit tested.
 */
final class Tree {

	/** @var array<string, array<string, mixed>> */
	private array $by_id = array();

	/** @var list<string> Flat order of element IDs. */
	private array $order = array();

	/**
	 * @param array<int, mixed> $elements Stored elements.
	 */
	public function __construct( array $elements ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) || empty( $element['id'] ) ) {
				continue;
			}
			$id = (string) $element['id'];
			if ( isset( $this->by_id[ $id ] ) ) {
				continue;
			}
			$element['id']       = $id;
			$element['parent']   = self::normalize_parent( $element['parent'] ?? 0 );
			$element['children'] = array_values( array_map( 'strval', (array) ( $element['children'] ?? array() ) ) );
			$element['settings'] = is_array( $element['settings'] ?? null ) ? $element['settings'] : array();

			$this->by_id[ $id ] = $element;
			$this->order[]      = $id;
		}
	}

	/**
	 * Elements in storage order.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function to_array(): array {
		return array_map( fn( string $id ): array => $this->by_id[ $id ], $this->order );
	}

	/**
	 * @return list<string>
	 */
	public function ids(): array {
		return $this->order;
	}

	public function count(): int {
		return count( $this->order );
	}

	public function has( string $id ): bool {
		return isset( $this->by_id[ $id ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get( string $id ): array {
		if ( ! isset( $this->by_id[ $id ] ) ) {
			throw new TreeError( 'element_not_found', sprintf( 'Element "%s" does not exist on this page.', $id ), 'Call bricks-get-page to see the element IDs on the page.' );
		}
		return $this->by_id[ $id ];
	}

	/**
	 * Ordered child IDs. Parent 0 means the root level.
	 *
	 * @param string|int $parent Element ID or 0.
	 * @return list<string>
	 */
	public function children( $parent ): array {
		if ( 0 === $parent ) {
			return array_values( array_filter( $this->order, fn( string $id ): bool => 0 === $this->by_id[ $id ]['parent'] ) );
		}
		return array_values( array_filter( $this->get( (string) $parent )['children'], array( $this, 'has' ) ) );
	}

	/**
	 * All descendant IDs of an element, depth first.
	 *
	 * @return list<string>
	 */
	public function descendants( string $id ): array {
		$result = array();
		foreach ( $this->children( $id ) as $child ) {
			$result[] = $child;
			array_push( $result, ...$this->descendants( $child ) );
		}
		return $result;
	}

	/**
	 * Nested view for reading.
	 *
	 * @param string|null $root      Start element, or null for the whole page.
	 * @param int         $max_depth Levels to include; deeper levels only report a count.
	 * @return list<array<string, mixed>>
	 */
	public function outline( ?string $root = null, int $max_depth = 50 ): array {
		$ids = null === $root ? $this->children( 0 ) : array( $this->get( $root )['id'] );
		return array_map( fn( string $id ): array => $this->node( $id, 1, $max_depth ), $ids );
	}

	/**
	 * Adds new elements built from nested specs.
	 *
	 * @param list<array<string, mixed>> $specs    Nested element specs: name, label?, settings?, children?.
	 * @param string|int                 $parent   Element ID or 0 for the root level.
	 * @param int|null                   $position Index among the parent's children; null appends.
	 * @return list<string> IDs of every created element, top-level ones first in each subtree.
	 */
	public function insert( array $specs, $parent, ?int $position, Ids $ids ): array {
		$parent = self::normalize_parent( $parent );
		if ( 0 !== $parent ) {
			$this->get( $parent );
		}

		$created = array();
		$roots   = array();
		foreach ( $specs as $spec ) {
			$roots[] = $this->create( $spec, $parent, $ids, $created );
		}

		$this->attach( $roots, $parent, $position, $created );
		return $created;
	}

	/**
	 * Removes an element and everything inside it.
	 *
	 * @return list<string> Removed IDs.
	 */
	public function remove( string $id ): array {
		$element = $this->get( $id );
		$removed = array_merge( array( $id ), $this->descendants( $id ) );

		$this->detach( $id, $element['parent'] );
		foreach ( $removed as $removed_id ) {
			unset( $this->by_id[ $removed_id ] );
		}
		$this->order = array_values( array_diff( $this->order, $removed ) );
		return $removed;
	}

	/**
	 * Moves an element (with its children) to a new parent and/or position.
	 *
	 * @param string|int $new_parent Element ID or 0.
	 */
	public function move( string $id, $new_parent, ?int $position ): void {
		$element    = $this->get( $id );
		$new_parent = self::normalize_parent( $new_parent );

		if ( 0 !== $new_parent ) {
			$this->get( $new_parent );
			if ( $new_parent === $id || in_array( $new_parent, $this->descendants( $id ), true ) ) {
				throw new TreeError( 'invalid_move', 'An element cannot be moved inside itself.', 'Choose a parent outside the element you are moving.' );
			}
		}

		$this->detach( $id, $element['parent'] );
		$this->by_id[ $id ]['parent'] = $new_parent;
		$this->attach( array( $id ), $new_parent, $position, array( $id ) );
	}

	/**
	 * Copies an element and its children, placing the copy right after it.
	 *
	 * @return array{id: string, created: list<string>}
	 */
	public function duplicate( string $id, Ids $ids ): array {
		$element  = $this->get( $id );
		$siblings = $this->children( $element['parent'] );
		$created  = array();
		$copy     = $this->create( $this->to_spec( $id ), $element['parent'], $ids, $created );

		$this->attach( array( $copy ), $element['parent'], (int) array_search( $id, $siblings, true ) + 1, $created );
		return array(
			'id'      => $copy,
			'created' => $created,
		);
	}

	/**
	 * Changes settings. In merge mode, a null value removes that key.
	 *
	 * @param array<string, mixed> $settings Settings patch.
	 * @param string               $mode     "merge" or "replace".
	 * @param string|null          $label    New label; "" removes it, null leaves it.
	 */
	public function update( string $id, array $settings, string $mode = 'merge', ?string $label = null ): void {
		$existing = $this->get( $id )['settings'];
		$current  = 'replace' === $mode ? array() : $existing;
		foreach ( $settings as $key => $value ) {
			if ( null === $value ) {
				unset( $current[ $key ] );
			} else {
				$current[ $key ] = $value;
			}
		}
		$this->by_id[ $id ]['settings'] = CustomCss::resolve( $current, CustomCss::element_selector( $id ) );

		if ( null !== $label ) {
			if ( '' === $label ) {
				unset( $this->by_id[ $id ]['label'] );
			} else {
				$this->by_id[ $id ]['label'] = $label;
			}
		}
	}

	/**
	 * Structural problems in the stored data.
	 *
	 * @return list<string>
	 */
	public function integrity(): array {
		$errors = array();
		foreach ( $this->by_id as $element ) {
			// Use the stored ID: PHP turns digit-only keys such as "372848" into integers.
			$id     = $element['id'];
			$parent = $element['parent'];
			if ( 0 !== $parent && ! isset( $this->by_id[ $parent ] ) ) {
				$errors[] = sprintf( 'Element %s points to missing parent %s.', $id, $parent );
			} elseif ( 0 !== $parent && ! in_array( $id, $this->by_id[ $parent ]['children'], true ) ) {
				$errors[] = sprintf( 'Element %s is not listed in the children of %s.', $id, $parent );
			}
			foreach ( $element['children'] as $child ) {
				if ( ! isset( $this->by_id[ $child ] ) ) {
					$errors[] = sprintf( 'Element %s lists missing child %s.', $id, $child );
				} elseif ( $this->by_id[ $child ]['parent'] !== $id ) {
					$errors[] = sprintf( 'Element %s lists child %s, whose parent is %s.', $id, $child, (string) $this->by_id[ $child ]['parent'] );
				}
			}
		}
		return $errors;
	}

	/**
	 * Nested spec of an existing element, used for duplication.
	 *
	 * @return array<string, mixed>
	 */
	public function to_spec( string $id ): array {
		$element = $this->get( $id );
		$spec    = array(
			'name'     => $element['name'],
			'settings' => $element['settings'],
			'children' => array_map( array( $this, 'to_spec' ), $this->children( $id ) ),
		);
		if ( isset( $element['label'] ) ) {
			$spec['label'] = $element['label'];
		}
		return $spec;
	}

	/**
	 * @param array<string, mixed> $spec    Element spec.
	 * @param string|int           $parent  Parent ID or 0.
	 * @param list<string>         $created Collects created IDs.
	 */
	private function create( array $spec, $parent, Ids $ids, array &$created ): string {
		$id      = $ids->next();
		$element = array(
			'id'       => $id,
			'name'     => (string) $spec['name'],
			'parent'   => $parent,
			'children' => array(),
			'settings' => CustomCss::resolve( is_array( $spec['settings'] ?? null ) ? $spec['settings'] : array(), CustomCss::element_selector( $id ) ),
		);
		if ( isset( $spec['label'] ) && '' !== $spec['label'] ) {
			$element['label'] = (string) $spec['label'];
		}

		$this->by_id[ $id ] = $element;
		$created[]          = $id;

		foreach ( (array) ( $spec['children'] ?? array() ) as $child ) {
			$this->by_id[ $id ]['children'][] = $this->create( $child, $id, $ids, $created );
		}
		return $id;
	}

	/**
	 * Places root IDs of new or moved subtrees under a parent.
	 *
	 * @param list<string> $roots IDs to place under the parent.
	 * @param string|int   $parent Parent ID or 0.
	 * @param list<string> $new_in_order IDs that are not yet in the flat order.
	 */
	private function attach( array $roots, $parent, ?int $position, array $new_in_order ): void {
		$siblings = $this->children( $parent );
		$siblings = array_values( array_diff( $siblings, $roots ) );
		$position = null === $position ? count( $siblings ) : max( 0, min( $position, count( $siblings ) ) );

		$order = array_values( array_diff( $this->order, $new_in_order ) );

		if ( 0 === $parent ) {
			$index = isset( $siblings[ $position ] ) ? (int) array_search( $siblings[ $position ], $order, true ) : count( $order );
		} else {
			array_splice( $siblings, $position, 0, $roots );
			$this->by_id[ $parent ]['children'] = $siblings;
			$index                              = count( $order );
		}

		array_splice( $order, $index, 0, $new_in_order );
		$this->order = $order;
	}

	/**
	 * @param string|int $parent Current parent.
	 */
	private function detach( string $id, $parent ): void {
		if ( 0 !== $parent && isset( $this->by_id[ $parent ] ) ) {
			$this->by_id[ $parent ]['children'] = array_values( array_diff( $this->by_id[ $parent ]['children'], array( $id ) ) );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function node( string $id, int $depth, int $max_depth ): array {
		$element = $this->by_id[ $id ];
		$node    = array(
			'id'       => $id,
			'name'     => $element['name'],
			'settings' => (object) $element['settings'],
		);
		if ( isset( $element['label'] ) ) {
			$node['label'] = $element['label'];
		}

		$children = $this->children( $id );
		if ( $depth >= $max_depth ) {
			$node['childCount'] = count( $children );
		} else {
			$node['children'] = array_map( fn( string $child ): array => $this->node( $child, $depth + 1, $max_depth ), $children );
		}
		return $node;
	}

	/**
	 * @param mixed $parent Raw parent value.
	 * @return string|int Element ID, or 0 for root.
	 */
	private static function normalize_parent( $parent ) {
		return ( null === $parent || '' === $parent || 0 === $parent || '0' === $parent ) ? 0 : (string) $parent;
	}
}
