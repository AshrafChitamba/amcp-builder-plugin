<?php
/**
 * Post helpers shared by tools.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Support;

use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Post type checks and the summary shape returned to the AI.
 */
final class Posts {

	/**
	 * Returns the post type object if it exists and is editable in wp-admin.
	 *
	 * @return \WP_Post_Type|WP_Error
	 */
	public static function editable_type( string $post_type ) {
		$type = get_post_type_object( $post_type );
		if ( ! $type || ! $type->show_ui ) {
			return Errors::make(
				'invalid_post_type',
				/* translators: %s: post type slug. */
				sprintf( __( 'Post type "%s" does not exist or cannot be edited.', 'mcp-builder' ), $post_type ),
				__( 'Use "page" or "post", or a custom post type slug registered on this site.', 'mcp-builder' )
			);
		}
		return $type;
	}

	/**
	 * Link that opens the post in its page builder.
	 */
	public static function builder_edit_link( WP_Post $post, string $builder ): ?string {
		return match ( $builder ) {
			PageBuilder::BRICKS    => add_query_arg( 'bricks', 'run', (string) get_permalink( $post ) ),
			PageBuilder::ELEMENTOR => add_query_arg(
				array(
					'post'   => $post->ID,
					'action' => 'elementor',
				),
				admin_url( 'post.php' )
			),
			default                => null,
		};
	}

	/**
	 * Summary of a post. The title is site content.
	 *
	 * @return array<string, mixed>
	 */
	public static function summary( WP_Post $post ): array {
		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$builder = PageBuilder::of( $post->ID );
		$lock    = wp_check_post_lock( $post->ID );
		$date    = get_post_datetime( $post, 'modified' );

		return array(
			'id'              => $post->ID,
			'title'           => Untrusted::text( $post->post_title ),
			'status'          => $post->post_status,
			'type'            => $post->post_type,
			'builder'         => $builder,
			'modified'        => $date ? $date->format( DATE_RFC3339 ) : null,
			'link'            => (string) get_permalink( $post ),
			'editLink'        => (string) get_edit_post_link( $post->ID, 'raw' ),
			'builderEditLink' => self::builder_edit_link( $post, $builder ),
			'lockedBy'        => $lock ? Untrusted::text( get_the_author_meta( 'display_name', $lock ), 60 ) : null,
		);
	}
}
