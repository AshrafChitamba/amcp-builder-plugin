<?php
/**
 * Elementor template and Theme Builder helpers.
 *
 * @package AcMcp
 */

namespace AcMcp\Elementor;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages become JSON tool errors, never HTML output.

use AcMcp\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Template types from the live document registry, and Pro Theme Builder conditions.
 *
 * VERIFY: Elementor 4.3.4 Documents_Manager::create() / get_document_types(),
 * Elementor Pro 4.3.1 Conditions_Manager::save_conditions() and its cache.
 */
final class Templates {

	public const POST_TYPE = 'elementor_library';

	/**
	 * Library template types registered on this site (header, footer, single, ... with Pro).
	 *
	 * @return list<string>
	 */
	public static function types(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return array();
		}
		$types = array();
		foreach ( \Elementor\Plugin::$instance->documents->get_document_types( array( 'show_in_library' => true ) ) as $name => $class ) {
			$cpt = (array) ( $class::get_property( 'cpt' ) ?? array() );
			// Atomic (v4) component types are read-only in AC MCP for Bricks and Elementor, and the kit is not a template.
			if ( in_array( self::POST_TYPE, $cpt, true ) && 'kit' !== $name && ! str_starts_with( (string) $name, 'e-' ) ) {
				$types[] = (string) $name;
			}
		}
		sort( $types );
		return $types;
	}

	/**
	 * Warnings about where a template of this type will not show.
	 *
	 * @return list<string>
	 */
	public static function theme_warnings( string $type ): array {
		if ( in_array( $type, array( 'header', 'footer' ), true ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return array( 'The active theme is a block theme, which does not load Elementor Pro headers and footers. The template is saved, but it only shows on the site with a classic theme such as Hello Elementor. Tell the user.' );
		}
		return array();
	}

	public static function has_theme_builder(): bool {
		return class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' );
	}

	/**
	 * @return object Elementor Pro conditions manager.
	 */
	public static function conditions_manager() {
		return \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager();
	}

	/**
	 * Validates condition strings such as "include/general" or "include/singular/page/42"
	 * against the registered conditions and returns them in save_conditions() format.
	 *
	 * @param mixed $conditions List of strings.
	 * @return list<list<string>>
	 */
	public static function parse_conditions( $conditions ): array {
		if ( ! is_array( $conditions ) ) {
			throw new TreeError( 'invalid_conditions', 'conditions must be a list of strings.' );
		}
		$manager = self::conditions_manager();
		$parsed  = array();
		foreach ( $conditions as $condition ) {
			if ( ! is_string( $condition ) || ! preg_match( '#^(include|exclude)/([a-z0-9_-]+)(?:/([a-z0-9_-]+))?(?:/(\d+))?$#', $condition, $match ) ) {
				throw new TreeError( 'invalid_condition', sprintf( 'Invalid condition "%s".', (string) $condition ), 'Use include|exclude/name[/sub_name[/id]], e.g. "include/general", "include/singular/page/42", "include/archive/category/5", "exclude/singular/front_page".' );
			}
			$name = $match[2];
			$sub  = $match[3] ?? '';
			if ( ! in_array( $name, array( 'general', 'singular', 'archive' ), true ) || ( '' !== $sub && ! $manager->get_condition( $sub ) ) ) {
				throw new TreeError( 'unknown_condition', sprintf( 'Unknown condition "%s".', $condition ), 'Top level must be general, singular or archive; sub conditions include front_page, page, post, not_found404, post_archive, category, search, author.' );
			}
			$parsed[] = array_values( array_filter( array( $match[1], $name, $sub, $match[4] ?? '' ), static fn( $part ): bool => '' !== $part ) );
		}
		return $parsed;
	}

	/**
	 * Stored conditions as strings.
	 *
	 * @return list<string>
	 */
	public static function conditions( int $post_id ): array {
		$conditions = get_post_meta( $post_id, '_elementor_conditions', true );
		return is_array( $conditions ) ? array_values( array_map( 'strval', $conditions ) ) : array();
	}
}
