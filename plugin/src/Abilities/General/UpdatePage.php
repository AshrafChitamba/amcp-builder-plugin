<?php
/**
 * The update-page tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\General;

use AmcpBuilder\Abilities\Ability;
use AmcpBuilder\Safety\Governance;
use AmcpBuilder\Support\Errors;
use AmcpBuilder\Support\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Changes a page's title, slug or status (publish, draft, ...).
 */
final class UpdatePage extends Ability {

	public function slug(): string {
		return 'update-page';
	}

	public function label(): string {
		return __( 'Update page', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Changes the title, URL slug or status of a page, post or builder template. Use status "publish" only when the user asked to publish: publishing makes a page public, '
			. 'and publishing a header, footer or template with conditions changes the whole site. WordPress keeps a revision of the previous title and status.';
	}

	protected function properties(): array {
		return array(
			'postId' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'title'  => array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
			),
			'slug'   => array(
				'type'      => 'string',
				'pattern'   => '^[a-z0-9-]+$',
				'maxLength' => 190,
			),
			'status' => array(
				'type' => 'string',
				'enum' => array( 'draft', 'pending', 'private', 'publish' ),
			),
			'dryRun' => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'postId' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_post', (int) $input['postId'] );
	}

	protected function run( array $input ) {
		$post = get_post( (int) $input['postId'] );
		if ( ! $post || 'trash' === $post->post_status ) {
			return Errors::make( 'post_not_found', __( 'That page does not exist.', 'amcp-builder' ), __( 'Call list-pages to find the right ID.', 'amcp-builder' ), 404 );
		}
		if ( 'publish' === ( $input['status'] ?? '' ) && ! current_user_can( 'publish_post', $post->ID ) ) {
			return Errors::make( 'cannot_publish', __( 'Your account cannot publish this page.', 'amcp-builder' ), __( 'Ask an editor or administrator to publish it.', 'amcp-builder' ), 403 );
		}
		$locked_by = Governance::locked_by( $post->ID );
		if ( $locked_by && 'deny' === $this->settings->get( 'post_lock' ) ) {
			/* translators: %s: user name. */
			return Errors::make( 'post_locked', sprintf( __( '%s has this page open in an editor.', 'amcp-builder' ), $locked_by ), __( 'Ask the user to close the editor, then try again.', 'amcp-builder' ), 409 );
		}

		$changes = array_filter(
			array(
				'post_title'  => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : null,
				'post_name'   => isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : null,
				'post_status' => $input['status'] ?? null,
			),
			static fn( $value ): bool => null !== $value
		);
		if ( ! $changes ) {
			return Errors::make( 'nothing_to_change', __( 'Pass a title, slug or status to change.', 'amcp-builder' ) );
		}
		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'  => true,
				'changes' => $changes,
			);
		}

		$updated = wp_update_post( wp_slash( array( 'ID' => $post->ID ) + $changes ), true );
		if ( is_wp_error( $updated ) ) {
			return Errors::make( 'update_failed', $updated->get_error_message(), '', 500 );
		}
		if ( 'bricks_template' === $post->post_type ) {
			\AmcpBuilder\Bricks\Templates::flush_cache();
		}

		return array(
			'dryRun' => false,
			'page'   => Posts::summary( get_post( $post->ID ) ),
		);
	}
}
