<?php
/**
 * Shared behaviour of Bricks tools.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Abilities\Ability;
use AcMcp\Bricks\BricksCatalog;
use AcMcp\Bricks\Store;
use AcMcp\Bricks\Tree;
use AcMcp\Support\Errors;
use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Bricks tools exist only when Bricks is the active theme.
 */
abstract class BricksAbility extends Ability {

	private ?BricksCatalog $catalog = null;

	public function group(): string {
		return 'bricks';
	}

	public function is_available(): bool {
		return null !== $this->environment->bricks_version() && Store::available() && BricksCatalog::available();
	}

	protected function authorize( array $input ): bool {
		return empty( $input['postId'] )
			? current_user_can( 'edit_posts' )
			: current_user_can( 'edit_post', (int) $input['postId'] );
	}

	protected function catalog(): BricksCatalog {
		return $this->catalog ??= new BricksCatalog();
	}

	/**
	 * @return array<string, mixed>
	 */
	protected static function post_id_property(): array {
		return array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Page ID from list-pages.',
		);
	}

	/**
	 * Loads the post and its editable Bricks elements.
	 *
	 * @return array{post: WP_Post, key: string, tree: Tree}|WP_Error
	 */
	protected function load( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'trash' === $post->post_status ) {
			return Errors::make( 'post_not_found', sprintf( /* translators: %d: post ID. */ __( 'Page %d does not exist.', 'ac-mcp-for-bricks-elementor' ), $post_id ), __( 'Call list-pages to find the right ID.', 'ac-mcp-for-bricks-elementor' ), 404 );
		}
		if ( ! Store::can_edit( $post_id ) ) {
			return Errors::make( 'bricks_access_denied', __( 'Bricks does not allow your user to edit this page.', 'ac-mcp-for-bricks-elementor' ), __( 'Ask an admin to give your role builder access in Bricks → Settings → Builder access.', 'ac-mcp-for-bricks-elementor' ), 403 );
		}

		$key = Store::area_key( $post );
		return array(
			'post' => $post,
			'key'  => $key,
			'tree' => new Tree( Store::read( $post_id, $key ) ),
		);
	}
}
