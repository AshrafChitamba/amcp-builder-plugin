<?php
/**
 * Bricks global classes store.
 *
 * @package AcMcp
 */

namespace AcMcp\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the bricks_global_classes option.
 *
 * Each class: { id, name, settings, category?, modified, user_id }.
 * VERIFY: option layout in Bricks 2.3.1 (includes/ajax.php save_post, globalClasses).
 */
final class GlobalClasses {

	public const OPTION = 'bricks_global_classes';

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function all(): array {
		$classes = get_option( self::OPTION, array() );
		return is_array( $classes ) ? array_values( array_filter( $classes, 'is_array' ) ) : array();
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function find( string $id_or_name ): ?array {
		foreach ( self::all() as $class ) {
			if ( ( $class['id'] ?? '' ) === $id_or_name || ( $class['name'] ?? '' ) === $id_or_name ) {
				return $class;
			}
		}
		return null;
	}

	/**
	 * Inserts or replaces a class (matched by id) and saves.
	 *
	 * @param array<string, mixed> $class Class data.
	 */
	public static function save( array $class ): void {
		$classes = self::all();
		$index   = array_search( $class['id'], array_column( $classes, 'id' ), true );

		$class['modified'] = time();
		$class['user_id']  = get_current_user_id();

		if ( false === $index ) {
			$classes[] = $class;
		} else {
			$classes[ $index ] = $class;
		}

		update_option( self::OPTION, $classes );
		update_option( 'bricks_global_classes_timestamp', time() );
		update_option( 'bricks_global_classes_user', get_current_user_id() );
	}
}
