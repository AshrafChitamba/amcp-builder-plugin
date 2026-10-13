<?php
/**
 * The bricks-list-templates tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

use McpBuilder\Bricks\Templates;
use McpBuilder\Support\Posts;
use McpBuilder\Support\Untrusted;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Lists Bricks templates with their type and display conditions.
 */
final class ListTemplates extends BricksAbility {

	public function slug(): string {
		return 'bricks-list-templates';
	}

	public function label(): string {
		return __( 'Bricks: list templates', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Lists Bricks templates (headers, footers, single and archive layouts, reusable sections, popups) with ID, type, status and display conditions. '
			. 'Edit a template\'s elements with the bricks-* page tools using its ID as postId. Only published templates are used on the site.';
	}

	protected function properties(): array {
		return array(
			'type' => array(
				'type'    => 'string',
				'enum'    => array_merge( array( 'any' ), Templates::types() ),
				'default' => 'any',
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
					'key'   => Templates::TYPE_META,
					'value' => $input['type'],
				),
			);
		}

		$items = array();
		foreach ( ( new WP_Query( $args ) )->posts as $post ) {
			$settings = get_post_meta( $post->ID, Templates::SETTINGS_META, true );
			$items[]  = array(
				'id'              => $post->ID,
				'title'           => Untrusted::text( $post->post_title ),
				'type'            => (string) get_post_meta( $post->ID, Templates::TYPE_META, true ),
				'status'          => $post->post_status,
				'conditions'      => is_array( $settings ) ? array_values( (array) ( $settings['templateConditions'] ?? array() ) ) : array(),
				'builderEditLink' => Posts::builder_edit_link( $post, 'bricks' ),
			);
		}
		return array(
			'templates' => $items,
			'notice'    => Untrusted::NOTICE,
		);
	}
}
