<?php
/**
 * Works out what an ability call will change.
 *
 * @package AcMcp
 */

namespace AcMcp\Safety;

defined( 'ABSPATH' ) || exit;

/**
 * Best-effort resolution of the post or option an ability writes to, used
 * to snapshot and lock-check abilities AC MCP for Bricks and Elementor does not own.
 */
final class Targets {

	private const POST_ID_KEYS = array( 'postId', 'post_id', 'pageId', 'page_id', 'documentId', 'document_id', 'templateId', 'template_id', 'id' );

	/**
	 * Official abilities that change one global option, matched by name fragment.
	 * VERIFY: Bricks 2.4 ability names (bricks/*); unmatched writes are audited without a snapshot.
	 */
	private const OPTION_FRAGMENTS = array(
		'global-class'    => 'bricks_global_classes',
		'variable'        => 'bricks_global_variables',
		'color-palette'   => 'bricks_color_palette',
		'theme-style'     => 'bricks_theme_styles',
		'component'       => 'bricks_components',
		'global-quer'     => 'bricks_global_queries',
		'global-settings' => 'bricks_global_settings',
	);

	/**
	 * @param mixed $input Ability input.
	 */
	public static function post_id( $input ): int {
		$input = is_object( $input ) ? get_object_vars( $input ) : $input;
		if ( ! is_array( $input ) ) {
			return 0;
		}
		foreach ( self::POST_ID_KEYS as $key ) {
			if ( isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) && (int) $input[ $key ] > 0 ) {
				return (int) $input[ $key ];
			}
		}
		return 0;
	}

	public static function option_for( string $ability ): ?string {
		if ( ! str_starts_with( $ability, 'bricks/' ) ) {
			return null;
		}
		foreach ( self::OPTION_FRAGMENTS as $fragment => $option ) {
			if ( str_contains( $ability, $fragment ) ) {
				return $option;
			}
		}
		return null;
	}
}
