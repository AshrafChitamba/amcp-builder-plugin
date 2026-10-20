<?php
/**
 * Reads and writes Bricks element data.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

use AmcpBuilder\Support\Errors;
use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Saves elements the same way the Bricks builder does (Ajax::save_post in
 * Bricks 2.3): revision first, Bricks' security check, post meta, editor
 * mode, then wp_update_post() so Bricks regenerates the CSS file.
 *
 * Bricks' own guard on update_post_metadata (builder access) is never
 * bypassed: a user without builder access gets an error, not a write.
 */
final class Store {

	public const CONTENT = '_bricks_page_content_2';
	public const HEADER  = '_bricks_page_header_2';
	public const FOOTER  = '_bricks_page_footer_2';

	public const AREA_KEYS = array( self::CONTENT, self::HEADER, self::FOOTER );

	public static function available(): bool {
		return class_exists( '\Bricks\Helpers' ) && class_exists( '\Bricks\Capabilities' );
	}

	/**
	 * Meta key holding the editable elements of a post. Header and footer
	 * templates keep their elements in their own area; everything else in content.
	 */
	public static function area_key( WP_Post $post ): string {
		if ( 'bricks_template' === $post->post_type ) {
			$type = get_post_meta( $post->ID, '_bricks_template_type', true );
			if ( 'header' === $type ) {
				return self::HEADER;
			}
			if ( 'footer' === $type ) {
				return self::FOOTER;
			}
		}
		return self::CONTENT;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function read( int $post_id, string $meta_key ): array {
		$elements = get_post_meta( $post_id, $meta_key, true );
		return is_array( $elements ) ? array_values( $elements ) : array();
	}

	/**
	 * True when the current user may open this post in Bricks.
	 */
	public static function can_edit( int $post_id ): bool {
		return \Bricks\Capabilities::current_user_can_use_builder( $post_id );
	}

	/**
	 * @param list<array<string, mixed>> $elements Flat element list.
	 * @return true|WP_Error
	 */
	public static function write( int $post_id, string $meta_key, array $elements ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return Errors::make( 'post_not_found', __( 'The page does not exist.', 'amcp-builder' ), '', 404 );
		}
		if ( ! self::can_edit( $post_id ) ) {
			return Errors::make( 'bricks_access_denied', __( 'Bricks does not allow your user to edit this page.', 'amcp-builder' ), __( 'Ask an admin to give your role builder access in Bricks → Settings → Builder access.', 'amcp-builder' ), 403 );
		}

		$area = array(
			self::CONTENT => 'content',
			self::HEADER  => 'header',
			self::FOOTER  => 'footer',
		)[ $meta_key ];

		// update_post_meta() unslashes its value, so slash first, as Bricks does.
		// VERIFY: Bricks\Helpers::security_check_elements_before_save( $elements, $post_id, $area ) (Bricks 2.3.1 helpers.php).
		$submitted = count( $elements );
		$elements  = \Bricks\Helpers::security_check_elements_before_save( wp_slash( array_values( $elements ) ), $post_id, $area );
		if ( ! is_array( $elements ) ) {
			return Errors::make( 'bricks_rejected', __( 'Bricks rejected the elements.', 'amcp-builder' ), '', 400 );
		}
		if ( count( $elements ) !== $submitted ) {
			return Errors::make( 'bricks_content_only', __( 'Bricks only lets your user edit existing content, not add or remove elements.', 'amcp-builder' ), __( 'Ask an admin for "Full access" in Bricks → Settings → Builder access, or only update existing elements.', 'amcp-builder' ), 403 );
		}

		$revision_id = self::save_revision( $post );

		if ( $elements ) {
			if ( $revision_id ) {
				update_metadata( 'post', $revision_id, $meta_key, $elements );
			}
			update_post_meta( $post_id, $meta_key, $elements );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}

		if ( self::CONTENT === $meta_key ) {
			update_post_meta( $post_id, '_bricks_editor_mode', $elements ? 'bricks' : 'WordPress' );
		}

		// Bricks regenerates the external CSS file on save_post.
		wp_update_post( array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );

		$stored = self::read( $post_id, $meta_key );
		if ( count( $stored ) !== count( $elements ) ) {
			return Errors::make( 'bricks_write_failed', __( 'The elements were not saved.', 'amcp-builder' ), __( 'Your user may lack Bricks builder access, or another plugin blocked the update.', 'amcp-builder' ), 500 );
		}
		return true;
	}

	/**
	 * Same as Bricks: a revision is saved even when post fields are unchanged.
	 */
	private static function save_revision( WP_Post $post ): int {
		add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		$revision_id = wp_save_post_revision( $post );
		remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		return is_int( $revision_id ) ? $revision_id : 0;
	}
}
