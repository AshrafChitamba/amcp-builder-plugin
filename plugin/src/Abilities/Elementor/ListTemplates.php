<?php
/**
 * The elementor-list-templates tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Templates;
use AcMcp\Support\Posts;
use AcMcp\Support\Untrusted;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Lists Elementor library templates.
 */
final class ListTemplates extends ElementorAbility {

	public function slug(): string {
		return 'elementor-list-templates';
	}

	public function label(): string {
		return __( 'Elementor: list templates', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Lists Elementor templates (sections, containers, pages and, with Elementor Pro, headers, footers, single and archive layouts, loop items, popups) with ID, type, status and Theme Builder conditions. '
			. 'Edit a template\'s content with the elementor-* page tools using its ID as postId. Theme templates only apply when published. availableTypes lists what elementor-create-template accepts.';
	}

	protected function properties(): array {
		return array(
			'type' => array(
				'type'      => 'string',
				'maxLength' => 40,
				'default'   => 'any',
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		$args = array(
			'post_type'      => Templates::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( 'any' !== $input['type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Template type is post meta.
				array(
					'key'   => '_elementor_template_type',
					'value' => sanitize_key( $input['type'] ),
				),
			);
		}

		$items = array();
		foreach ( ( new WP_Query( $args ) )->posts as $post ) {
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}
			$items[] = array(
				'id'              => $post->ID,
				'title'           => Untrusted::text( $post->post_title ),
				'type'            => (string) get_post_meta( $post->ID, '_elementor_template_type', true ),
				'status'          => $post->post_status,
				'conditions'      => Templates::conditions( $post->ID ),
				'builderEditLink' => Posts::builder_edit_link( $post, 'elementor' ),
			);
		}

		return array(
			'templates'      => $items,
			'availableTypes' => Templates::types(),
			'themeBuilder'   => Templates::has_theme_builder(),
			'warnings'       => Templates::theme_warnings( 'header' ),
			'notice'         => Untrusted::NOTICE,
		);
	}
}
