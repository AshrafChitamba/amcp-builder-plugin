<?php
/**
 * Summary of what a write changed.
 *
 * @package AcMcp
 */

namespace AcMcp\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Compares two flat element lists by ID. Shared by the Bricks and Elementor tools.
 */
final class Diff {

	/**
	 * @param list<array<string, mixed>> $before Elements before.
	 * @param list<array<string, mixed>> $after  Elements after.
	 * @return array{added: list<string>, removed: list<string>, changed: list<string>, elementCount: int}
	 */
	public static function between( array $before, array $after ): array {
		$old = array_column( $before, null, 'id' );
		$new = array_column( $after, null, 'id' );

		$changed = array();
		foreach ( array_intersect_key( $new, $old ) as $id => $element ) {
			if ( $element != $old[ $id ] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- Structural comparison of nested arrays.
				$changed[] = (string) $id;
			}
		}

		return array(
			'added'        => array_map( 'strval', array_keys( array_diff_key( $new, $old ) ) ),
			'removed'      => array_map( 'strval', array_keys( array_diff_key( $old, $new ) ) ),
			'changed'      => $changed,
			'elementCount' => count( $after ),
		);
	}
}
