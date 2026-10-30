<?php
/**
 * The elementor-create-template tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Templates;
use AcMcp\Support\Errors;
use AcMcp\Support\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Creates an empty Elementor template.
 */
final class CreateTemplate extends ElementorAbility {

	public function slug(): string {
		return 'elementor-create-template';
	}

	public function label(): string {
		return __( 'Elementor: create template', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Creates an empty Elementor template of a type listed by elementor-list-templates (availableTypes), e.g. container, section, page, and with Elementor Pro header, footer, single-post, single-page, archive, loop-item, popup. '
			. 'Then add content with elementor-add-elements (postId = the new ID) and, for theme templates, set where it shows with elementor-set-conditions. '
			. 'Templates are created as drafts; theme templates only apply once published (update-page). Confirm with the user before publishing headers and footers.';
	}

	protected function properties(): array {
		return array(
			'title'  => array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
			),
			'type'   => array(
				'type'      => 'string',
				'maxLength' => 40,
			),
			'dryRun' => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'title', 'type' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	protected function run( array $input ) {
		$types = Templates::types();
		if ( ! in_array( $input['type'], $types, true ) ) {
			$hint = __( 'Use one of: ', 'ac-mcp-for-bricks-elementor' ) . implode( ', ', $types ) . '. Header, footer and single types need Elementor Pro.';
			return Errors::make( 'invalid_template_type', sprintf( /* translators: %s: type. */ __( 'Template type "%s" is not available on this site.', 'ac-mcp-for-bricks-elementor' ), (string) $input['type'] ), $hint );
		}
		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'      => true,
				'wouldCreate' => array(
					'title' => sanitize_text_field( $input['title'] ),
					'type'  => $input['type'],
				),
			);
		}

		// VERIFY: Documents_Manager::create() sets _elementor_edit_mode, _elementor_template_type and the library type term.
		$document = \Elementor\Plugin::$instance->documents->create(
			$input['type'],
			array(
				'post_title'  => sanitize_text_field( $input['title'] ),
				'post_status' => 'draft',
			)
		);
		if ( is_wp_error( $document ) || ! $document ) {
			return Errors::make( 'create_failed', is_wp_error( $document ) ? $document->get_error_message() : __( 'Elementor could not create the template.', 'ac-mcp-for-bricks-elementor' ), '', 500 );
		}

		$post = get_post( $document->get_main_id() );
		return array(
			'dryRun'   => false,
			'template' => array(
				'id'     => $post->ID,
				'type'   => $input['type'],
				'status' => $post->post_status,
			) + array_intersect_key( Posts::summary( $post ), array_flip( array( 'editLink', 'builderEditLink' ) ) ),
			'warnings' => Templates::theme_warnings( (string) $input['type'] ),
		);
	}
}
