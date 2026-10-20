<?php
/**
 * Bricks template helpers.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use AmcpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Template types, condition validation and cache flushing for Bricks templates.
 *
 * VERIFY: Bricks 2.3.1 setup.php (types), settings/settings-template.php
 * (templateConditions fields) and database.php screen_conditions (scoring).
 */
final class Templates {

	public const POST_TYPE     = 'bricks_template';
	public const TYPE_META     = '_bricks_template_type';
	public const SETTINGS_META = '_bricks_template_settings';

	public const TYPES = array( 'header', 'footer', 'content', 'section', 'popup', 'archive', 'search', 'error' );

	public const MAINS = array( 'any', 'frontpage', 'postType', 'ids', 'terms', 'archiveType', 'search', 'error' );

	public const ARCHIVE_TYPES = array( 'any', 'postType', 'author', 'date', 'term' );

	/**
	 * Types available on this site (WooCommerce adds its own).
	 *
	 * @return list<string>
	 */
	public static function types(): array {
		$types = self::TYPES;
		if ( class_exists( 'WooCommerce' ) ) {
			array_push( $types, 'wc_archive', 'wc_product' );
		}
		return $types;
	}

	/**
	 * Validates conditions given by the AI and converts them to Bricks' format.
	 *
	 * Input per condition: main, plus postTypes, ids, includeChildren, terms
	 * ("taxonomy::term_id"), archiveTypes, archivePostTypes, archiveTerms,
	 * hookName, hookPriority, exclude.
	 *
	 * @param mixed $conditions Conditions from the tool input.
	 * @return list<array<string, mixed>>
	 */
	public static function conditions( $conditions, Ids $ids ): array {
		if ( ! is_array( $conditions ) ) {
			throw new TreeError( 'invalid_conditions', 'conditions must be an array.' );
		}
		$clean = array();
		foreach ( $conditions as $condition ) {
			$main = is_array( $condition ) ? (string) ( $condition['main'] ?? '' ) : '';
			if ( ! in_array( $main, self::MAINS, true ) ) {
				throw new TreeError( 'invalid_condition', sprintf( 'Unknown condition "%s".', $main ), 'Use one of: ' . implode( ', ', self::MAINS ) . '.' );
			}
			$item = array(
				'id'   => $ids->next(),
				'main' => $main,
			);

			if ( 'postType' === $main ) {
				$item['postType'] = self::post_types( $condition['postTypes'] ?? array() );
			} elseif ( 'ids' === $main ) {
				$item['ids'] = array_values( array_filter( array_map( 'absint', (array) ( $condition['ids'] ?? array() ) ) ) );
				if ( ! $item['ids'] ) {
					throw new TreeError( 'invalid_condition', 'Condition "ids" needs at least one post ID in ids.' );
				}
				if ( ! empty( $condition['includeChildren'] ) ) {
					$item['idsIncludeChildren'] = true;
				}
			} elseif ( 'terms' === $main ) {
				$item['terms'] = self::terms( $condition['terms'] ?? array(), false );
			} elseif ( 'archiveType' === $main ) {
				$types = array_values( array_intersect( (array) ( $condition['archiveTypes'] ?? array( 'any' ) ), self::ARCHIVE_TYPES ) );
				if ( ! $types ) {
					throw new TreeError( 'invalid_condition', 'archiveTypes must contain any, postType, author, date or term.' );
				}
				$item['archiveType'] = $types;
				if ( in_array( 'postType', $types, true ) && ! empty( $condition['archivePostTypes'] ) ) {
					$item['archivePostTypes'] = self::post_types( $condition['archivePostTypes'] );
				}
				if ( in_array( 'term', $types, true ) && ! empty( $condition['archiveTerms'] ) ) {
					$item['archiveTerms'] = self::terms( $condition['archiveTerms'], true );
				}
			}

			if ( ! empty( $condition['hookName'] ) ) {
				$item['hookName']     = sanitize_key( (string) $condition['hookName'] );
				$item['hookPriority'] = (int) ( $condition['hookPriority'] ?? 10 );
			}
			if ( ! empty( $condition['exclude'] ) ) {
				$item['exclude'] = true; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Bricks condition flag, not a query argument.
			}
			$clean[] = $item;
		}
		return $clean;
	}

	/**
	 * Bricks caches template lookups in the object cache; bump its key after changes.
	 */
	public static function flush_cache(): void {
		wp_cache_set( 'last_changed', microtime(), 'bricks_bricks_template' );
	}

	/**
	 * @param mixed $types Post type slugs.
	 * @return list<string>
	 */
	private static function post_types( $types ): array {
		$clean = array_values( array_filter( (array) $types, static fn( $type ): bool => is_string( $type ) && post_type_exists( $type ) ) );
		if ( ! $clean ) {
			throw new TreeError( 'invalid_condition', 'postTypes must list existing post type slugs, e.g. ["post"].' );
		}
		return $clean;
	}

	/**
	 * @param mixed $terms "taxonomy::term_id" strings.
	 * @return list<string>
	 */
	private static function terms( $terms, bool $allow_all ): array {
		$clean = array();
		foreach ( (array) $terms as $term ) {
			if ( is_string( $term ) && preg_match( '/^([a-z0-9_-]+)::(\d+|all)$/', $term, $match ) && taxonomy_exists( $match[1] ) && ( $allow_all || 'all' !== $match[2] ) ) {
				$clean[] = $term;
			}
		}
		if ( ! $clean ) {
			throw new TreeError( 'invalid_condition', 'terms must be "taxonomy::term_id" strings, e.g. "category::5".' );
		}
		return $clean;
	}
}
