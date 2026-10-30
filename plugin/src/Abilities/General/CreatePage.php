<?php
/**
 * The create-page tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\General;

use AcMcp\Abilities\Ability;
use AcMcp\Support\Errors;
use AcMcp\Support\PageBuilder;
use AcMcp\Support\Posts;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Creates an empty page set up for Bricks or Elementor.
 */
final class CreatePage extends Ability {

	public function slug(): string {
		return 'create-page';
	}

	public function label(): string {
		return __( 'Create page', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Creates a new, empty page (or other post type) and marks it for the chosen builder, so builder tools can add content to it. '
			. 'Pages are created as drafts unless you pass another status; prefer drafts and let a human publish. '
			. 'Returns the new page ID and edit links. With dryRun true nothing is saved. '
			. 'Pitfall: the post type must be enabled for the builder (Bricks: Bricks → Settings → Post types; Elementor: Elementor → Settings → Post types).';
	}

	protected function properties(): array {
		return array(
			'title'    => array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
			),
			'builder'  => array(
				'type' => 'string',
				'enum' => array( PageBuilder::BRICKS, PageBuilder::ELEMENTOR ),
			),
			'postType' => array(
				'type'      => 'string',
				'default'   => 'page',
				'maxLength' => 20,
			),
			'status'   => array(
				'type'    => 'string',
				'enum'    => array( 'draft', 'pending', 'private', 'publish' ),
				'default' => 'draft',
			),
			'dryRun'   => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'title', 'builder' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		$type = get_post_type_object( (string) $input['postType'] );
		return $type ? current_user_can( $type->cap->create_posts ) : current_user_can( 'edit_posts' );
	}

	protected function run( array $input ) {
		$type = Posts::editable_type( $input['postType'] );
		if ( is_wp_error( $type ) ) {
			return $type;
		}

		$supported = $this->check_builder( $input['builder'], $type->name );
		if ( is_wp_error( $supported ) ) {
			return $supported;
		}

		if ( 'publish' === $input['status'] && ! current_user_can( $type->cap->publish_posts ) ) {
			return Errors::make( 'cannot_publish', __( 'You are not allowed to publish this post type.', 'ac-mcp-for-bricks-elementor' ), __( 'Create it as a draft instead.', 'ac-mcp-for-bricks-elementor' ), 403 );
		}

		$postarr = array(
			'post_title'  => sanitize_text_field( $input['title'] ),
			'post_type'   => $type->name,
			'post_status' => $input['status'],
		);

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'      => true,
				'wouldCreate' => $postarr + array( 'builder' => $input['builder'] ),
			);
		}

		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) ) {
			return Errors::make( 'create_failed', $post_id->get_error_message(), '', 500 );
		}

		$marked = PageBuilder::BRICKS === $input['builder'] ? $this->mark_bricks( $post_id ) : $this->mark_elementor( $post_id );
		if ( is_wp_error( $marked ) ) {
			wp_delete_post( $post_id, true );
			return $marked;
		}

		return array(
			'dryRun' => false,
			'page'   => Posts::summary( get_post( $post_id ) ),
		);
	}

	/**
	 * @return true|WP_Error
	 */
	private function check_builder( string $builder, string $post_type ) {
		if ( PageBuilder::BRICKS === $builder ) {
			if ( null === $this->environment->bricks_version() ) {
				return Errors::make( 'builder_inactive', __( 'Bricks is not the active theme on this site.', 'ac-mcp-for-bricks-elementor' ), __( 'Call site-info to see which builders are active.', 'ac-mcp-for-bricks-elementor' ) );
			}
			// VERIFY: Bricks stores enabled post types in bricks_global_settings['postTypes'] (Bricks 2.4).
			$settings = get_option( 'bricks_global_settings', array() );
			$enabled  = is_array( $settings ) && isset( $settings['postTypes'] ) ? (array) $settings['postTypes'] : array( 'page' );
		} else {
			if ( null === $this->environment->elementor_version() ) {
				return Errors::make( 'builder_inactive', __( 'Elementor is not active on this site.', 'ac-mcp-for-bricks-elementor' ), __( 'Call site-info to see which builders are active.', 'ac-mcp-for-bricks-elementor' ) );
			}
			// VERIFY: Elementor stores enabled post types in the elementor_cpt_support option (default page, post).
			$enabled = (array) get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
		}

		if ( ! in_array( $post_type, $enabled, true ) ) {
			return Errors::make(
				'post_type_not_enabled',
				/* translators: 1: post type, 2: builder name. */
				sprintf( __( 'Post type "%1$s" is not enabled for %2$s.', 'ac-mcp-for-bricks-elementor' ), $post_type, ucfirst( $builder ) ),
				__( 'Ask a site admin to enable it in the builder\'s settings, or use postType "page".', 'ac-mcp-for-bricks-elementor' )
			);
		}
		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	private function mark_bricks( int $post_id ) {
		// VERIFY: Bricks treats a post as a Bricks page when _bricks_editor_mode is "bricks".
		update_post_meta( $post_id, '_bricks_editor_mode', 'bricks' );
		if ( 'bricks' !== get_post_meta( $post_id, '_bricks_editor_mode', true ) ) {
			return Errors::make( 'bricks_access_denied', __( 'Bricks refused to mark the page as a Bricks page.', 'ac-mcp-for-bricks-elementor' ), __( 'Your user needs builder access in Bricks → Settings → Builder access.', 'ac-mcp-for-bricks-elementor' ), 403 );
		}
		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	private function mark_elementor( int $post_id ) {
		// VERIFY: Documents API in Elementor 4.3 (core/documents-manager.php, core/base/document.php).
		$document = \Elementor\Plugin::$instance->documents->get( $post_id, false );
		if ( ! $document || false === $document->save( array( 'elements' => array() ) ) ) {
			return Errors::make( 'elementor_save_failed', __( 'Elementor could not set up the page.', 'ac-mcp-for-bricks-elementor' ), __( 'Check that your user can edit this post type with Elementor.', 'ac-mcp-for-bricks-elementor' ), 500 );
		}
		$document->set_is_built_with_elementor( true );
		return true;
	}
}
