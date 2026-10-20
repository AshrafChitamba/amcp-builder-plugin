<?php
/**
 * Detects which builder a post was built with.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the builder markers each builder stores in post meta.
 */
final class PageBuilder {

	public const BRICKS    = 'bricks';
	public const ELEMENTOR = 'elementor';
	public const NONE      = 'none';

	/**
	 * Returns bricks, elementor or none.
	 */
	public static function of( int $post_id ): string {
		// VERIFY: Bricks 2.x sets _bricks_editor_mode = "bricks"; older posts may only have content meta.
		if ( 'bricks' === get_post_meta( $post_id, '_bricks_editor_mode', true ) || metadata_exists( 'post', $post_id, '_bricks_page_content_2' ) ) {
			return self::BRICKS;
		}
		if ( 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return self::ELEMENTOR;
		}
		return self::NONE;
	}

	/**
	 * Meta query matching posts built with the given builder.
	 *
	 * @return array<string, mixed>
	 */
	public static function meta_query( string $builder ): array {
		return match ( $builder ) {
			self::BRICKS    => array(
				'relation' => 'OR',
				array(
					'key'   => '_bricks_editor_mode',
					'value' => 'bricks',
				),
				array(
					'key'     => '_bricks_page_content_2',
					'compare' => 'EXISTS',
				),
			),
			self::ELEMENTOR => array(
				'key'   => '_elementor_edit_mode',
				'value' => 'builder',
			),
			default         => array(),
		};
	}
}
