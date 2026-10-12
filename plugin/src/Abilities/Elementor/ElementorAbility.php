<?php
/**
 * Shared behaviour of Elementor tools.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Abilities\Ability;
use McpBuilder\Elementor\ElementorCatalog;
use McpBuilder\Elementor\Store;
use McpBuilder\Elementor\Tree;
use McpBuilder\Support\Errors;
use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor tools exist only when Elementor is active. They edit classic
 * Containers and widgets; Atomic (v4) elements are read-only.
 */
abstract class ElementorAbility extends Ability {

	private ?ElementorCatalog $catalog = null;

	public function group(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return null !== $this->environment->elementor_version() && Store::available() && ElementorCatalog::available();
	}

	protected function authorize( array $input ): bool {
		return empty( $input['postId'] )
			? current_user_can( 'edit_posts' )
			: current_user_can( 'edit_post', (int) $input['postId'] );
	}

	protected function catalog(): ElementorCatalog {
		return $this->catalog ??= new ElementorCatalog();
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
	 * Loads the post and its Elementor elements.
	 *
	 * @return array{post: WP_Post, tree: Tree}|WP_Error
	 */
	protected function load( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'trash' === $post->post_status ) {
			return Errors::make( 'post_not_found', sprintf( /* translators: %d: post ID. */ __( 'Page %d does not exist.', 'mcp-builder' ), $post_id ), __( 'Call list-pages to find the right ID.', 'mcp-builder' ), 404 );
		}
		if ( ! Store::can_edit( $post_id ) ) {
			return Errors::make( 'elementor_access_denied', __( 'Elementor does not allow your user to edit this page.', 'mcp-builder' ), __( 'Check that the post type is enabled in Elementor → Settings and that your role has Elementor access (Elementor → Role Manager).', 'mcp-builder' ), 403 );
		}

		return array(
			'post' => $post,
			'tree' => new Tree( Store::read( $post_id ) ),
		);
	}
}
