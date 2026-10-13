<?php
/**
 * Bricks colors, variables and theme styles.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the global design options and regenerates their CSS.
 *
 * VERIFY: Bricks 2.3.1 option layouts (helpers.php save_global_variables_in_db,
 * database.php default_color_palette, theme-styles.php) and
 * Ajax::generate_style_manager_css_file() (ajax.php:271).
 */
final class DesignTokens {

	public const PALETTE    = 'bricks_color_palette';
	public const VARIABLES  = 'bricks_global_variables';
	public const CATEGORIES = 'bricks_global_variables_categories';
	public const STYLES     = 'bricks_theme_styles';

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function palettes(): array {
		$palettes = get_option( self::PALETTE, array() );
		if ( ( ! is_array( $palettes ) || ! $palettes ) && class_exists( '\Bricks\Database' ) && method_exists( '\Bricks\Database', 'default_color_palette' ) ) {
			$palettes = \Bricks\Database::default_color_palette();
		}
		return is_array( $palettes ) ? array_values( $palettes ) : array();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function variables(): array {
		$variables = get_option( self::VARIABLES, array() );
		return is_array( $variables ) ? array_values( $variables ) : array();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function categories(): array {
		$categories = get_option( self::CATEGORIES, array() );
		return is_array( $categories ) ? array_values( $categories ) : array();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function theme_styles(): array {
		$styles = get_option( self::STYLES, array() );
		return is_array( $styles ) ? $styles : array();
	}

	/**
	 * Rebuilds the style manager CSS file that holds palette and variable CSS.
	 */
	public static function regenerate_css(): void {
		if ( class_exists( '\Bricks\Ajax' ) && method_exists( '\Bricks\Ajax', 'generate_style_manager_css_file' ) ) {
			\Bricks\Ajax::generate_style_manager_css_file();
		}
	}

	/**
	 * A CSS custom property name without the leading dashes: lowercase, a-z, 0-9 and hyphens.
	 */
	public static function variable_name( string $name ): string {
		return trim( (string) preg_replace( '/[^a-z0-9-]+/', '-', strtolower( ltrim( trim( $name ), '-' ) ) ), '-' );
	}
}
