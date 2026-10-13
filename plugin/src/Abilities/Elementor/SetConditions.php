<?php
/**
 * The elementor-set-conditions tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Elementor\Templates;
use McpBuilder\Support\Errors;
use McpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Sets where an Elementor Pro theme template is displayed.
 */
final class SetConditions extends ElementorAbility {

	public function slug(): string {
		return 'elementor-set-conditions';
	}

	public function label(): string {
		return __( 'Elementor: set display conditions', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Replaces the Elementor Pro Theme Builder display conditions of a header, footer, single, archive or popup template. Conditions are strings: '
			. '"include/general" (entire site), "include/singular/front_page", "include/singular/page/42", "include/singular/post", "include/archive/category/5", "include/archive/search", "exclude/singular/page/7". '
			. 'An empty list removes all conditions. Changing a published header or footer changes the whole site: confirm with the user. A snapshot is taken first.';
	}

	public function is_available(): bool {
		return parent::is_available() && Templates::has_theme_builder();
	}

	protected function properties(): array {
		return array(
			'templateId' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'conditions' => array(
				'type'     => 'array',
				'maxItems' => 30,
				'items'    => array(
					'type'      => 'string',
					'maxLength' => 120,
				),
			),
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
		if ( ! $post ) {
			return Errors::make( 'template_not_found', __( 'That template does not exist.', 'mcp-builder' ), __( 'Call elementor-list-templates.', 'mcp-builder' ), 404 );
		}
		try {
			$parsed = Templates::parse_conditions( $input['conditions'] );
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$result = array(
			'templateId' => $post->ID,
			'before'     => Templates::conditions( $post->ID ),
			'after'      => array_map( static fn( array $parts ): string => implode( '/', $parts ), $parsed ),
			'warnings'   => array_merge(
				'publish' === $post->post_status ? array() : array( 'The template is a draft: Elementor only applies published theme templates.' ),
				Templates::theme_warnings( (string) get_post_meta( $post->ID, '_elementor_template_type', true ) )
			),
		);
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		// save_conditions() returns false when the post is not a theme template; it regenerates the cache itself.
		if ( false === Templates::conditions_manager()->save_conditions( $post->ID, $parsed ) ) {
			return Errors::make( 'not_theme_template', __( 'Only Theme Builder templates (header, footer, single, archive, popup) take display conditions.', 'mcp-builder' ), __( 'Check the template type with elementor-list-templates.', 'mcp-builder' ) );
		}

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
