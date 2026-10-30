<?php
/**
 * The bricks-create-template tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\Ids;
use AcMcp\Bricks\Templates;
use AcMcp\Support\Errors;
use AcMcp\Support\Posts;
use AcMcp\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Creates an empty Bricks template.
 */
final class CreateTemplate extends BricksAbility {

	public function slug(): string {
		return 'bricks-create-template';
	}

	public function label(): string {
		return __( 'Bricks: create template', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Creates an empty Bricks template: header, footer, content (single layout), section (reusable block), popup, archive, search or error. '
			. 'Then add elements with bricks-add-elements using the returned ID as postId, and set where it applies with bricks-set-template-conditions. '
			. 'Templates are created as drafts and only affect the site once published (update-page with status "publish"); confirm with the user first, because a published header or footer changes every page.';
	}

	protected function properties(): array {
		return array(
			'title'      => array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
			),
			'type'       => array(
				'type' => 'string',
				'enum' => Templates::types(),
			),
			'conditions' => self::conditions_property(),
			'dryRun'     => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'title', 'type' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		$type = get_post_type_object( Templates::POST_TYPE );
		return $type ? current_user_can( $type->cap->create_posts ) : current_user_can( 'edit_posts' );
	}

	protected function run( array $input ) {
		try {
			$conditions = isset( $input['conditions'] ) ? Templates::conditions( $input['conditions'], new Ids() ) : array();
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'      => true,
				'wouldCreate' => array(
					'title'      => sanitize_text_field( $input['title'] ),
					'type'       => $input['type'],
					'conditions' => $conditions,
				),
			);
		}

		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_title'  => sanitize_text_field( $input['title'] ),
					'post_type'   => Templates::POST_TYPE,
					'post_status' => 'draft',
				)
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return Errors::make( 'create_failed', $post_id->get_error_message(), '', 500 );
		}

		update_post_meta( $post_id, Templates::TYPE_META, $input['type'] );
		update_post_meta( $post_id, '_bricks_editor_mode', 'bricks' );
		if ( $conditions ) {
			update_post_meta( $post_id, Templates::SETTINGS_META, array( 'templateConditions' => $conditions ) );
		}
		Templates::flush_cache();

		return array(
			'dryRun'   => false,
			'template' => array(
				'id'         => $post_id,
				'type'       => $input['type'],
				'status'     => 'draft',
				'conditions' => $conditions,
			) + array_intersect_key( Posts::summary( get_post( $post_id ) ), array_flip( array( 'editLink', 'builderEditLink' ) ) ),
		);
	}

	/**
	 * JSON Schema of the conditions list, shared with bricks-set-template-conditions.
	 *
	 * @return array<string, mixed>
	 */
	public static function conditions_property(): array {
		return array(
			'type'        => 'array',
			'maxItems'    => 30,
			'description' => 'Where the template applies. Highest score wins: ids 10, frontpage 9, terms/search/error 8, postType 7, archive any 3, any 2. Add exclude: true to exclude.',
			'items'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'main'             => array(
						'type' => 'string',
						'enum' => Templates::MAINS,
					),
					'postTypes'        => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'ids'              => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
					'includeChildren'  => array( 'type' => 'boolean' ),
					'terms'            => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => '"taxonomy::term_id", e.g. "category::5".',
					),
					'archiveTypes'     => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
							'enum' => Templates::ARCHIVE_TYPES,
						),
					),
					'archivePostTypes' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'archiveTerms'     => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'hookName'         => array(
						'type'        => 'string',
						'description' => 'Section templates only: WordPress or Bricks hook to render on.',
					),
					'hookPriority'     => array( 'type' => 'integer' ),
					'exclude'          => array( 'type' => 'boolean' ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Schema of a Bricks condition flag, not a query argument.
				),
				'required'             => array( 'main' ),
				'additionalProperties' => false,
			),
		);
	}
}
