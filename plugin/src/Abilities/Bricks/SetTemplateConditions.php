<?php
/**
 * The bricks-set-template-conditions tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

use McpBuilder\Bricks\Ids;
use McpBuilder\Bricks\Templates;
use McpBuilder\Support\Errors;
use McpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces where a Bricks template applies.
 */
final class SetTemplateConditions extends BricksAbility {

	public function slug(): string {
		return 'bricks-set-template-conditions';
	}

	public function label(): string {
		return __( 'Bricks: set template conditions', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Replaces the display conditions of a Bricks template (where a header, footer, single, archive layout or popup is used). '
			. 'Examples: entire site [{"main":"any"}]; front page only [{"main":"frontpage"}]; all posts [{"main":"postType","postTypes":["post"]}]; one page [{"main":"ids","ids":[42]}]; '
			. 'blog archives [{"main":"archiveType","archiveTypes":["postType"],"archivePostTypes":["post"]}]. An empty list removes all conditions. '
			. 'Changing conditions of a published header or footer changes the whole site: confirm with the user. A snapshot is taken first.';
	}

	protected function properties(): array {
		return array(
			'templateId' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'conditions' => CreateTemplate::conditions_property(),
			'dryRun'     => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'templateId', 'conditions' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_post', (int) $input['templateId'] );
	}

	protected function run( array $input ) {
		$post = get_post( (int) $input['templateId'] );
		if ( ! $post || Templates::POST_TYPE !== $post->post_type ) {
			return Errors::make( 'template_not_found', __( 'That ID is not a Bricks template.', 'mcp-builder' ), __( 'Call bricks-list-templates to find template IDs.', 'mcp-builder' ), 404 );
		}

		try {
			$conditions = Templates::conditions( $input['conditions'], new Ids() );
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$settings = get_post_meta( $post->ID, Templates::SETTINGS_META, true );
		$settings = is_array( $settings ) ? $settings : array();
		$before   = $settings['templateConditions'] ?? array();
		if ( $conditions ) {
			$settings['templateConditions'] = $conditions;
		} else {
			unset( $settings['templateConditions'] );
		}

		$result = array(
			'templateId' => $post->ID,
			'before'     => $before,
			'after'      => $conditions,
			'warnings'   => 'publish' === $post->post_status ? array() : array( 'The template is not published, so the conditions have no effect until it is.' ),
		);
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		if ( $settings ) {
			update_post_meta( $post->ID, Templates::SETTINGS_META, $settings );
		} else {
			delete_post_meta( $post->ID, Templates::SETTINGS_META );
		}
		Templates::flush_cache();
		wp_update_post( array( 'ID' => $post->ID ) );

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
