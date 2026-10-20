<?php
/**
 * The elementor-upsert-code-snippet tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Elementor;

use AmcpBuilder\Elementor\Templates;
use AmcpBuilder\Support\Errors;
use AmcpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Creates or updates an Elementor Pro Custom Code snippet (head/body code).
 */
final class UpsertCodeSnippet extends ElementorAbility {

	private const LOCATIONS = array(
		'head'       => 'elementor_head',
		'body_start' => 'elementor_body_start',
		'body_end'   => 'elementor_body_end',
	);

	public function slug(): string {
		return 'elementor-upsert-code-snippet';
	}

	public function label(): string {
		return __( 'Elementor: add or update custom code snippet', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Creates or updates an Elementor Pro Custom Code snippet: HTML, CSS (in <style>) or JavaScript (in <script>) printed in the head, at body start or at body end. '
			. 'Conditions use the Theme Builder format ("include/general", "include/singular/page/42"); with no conditions the snippet prints on every page. '
			. 'Snippets are created as drafts; publish with update-page once the user approves. Custom code is off by default and needs an administrator.';
	}

	public function group(): string {
		return 'custom-code';
	}

	public function is_available(): bool {
		return parent::is_available() && post_type_exists( 'elementor_snippet' );
	}

	protected function properties(): array {
		return array(
			'snippetId'  => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'title'      => array(
				'type'      => 'string',
				'maxLength' => 200,
			),
			'code'       => array(
				'type'      => 'string',
				'maxLength' => 100000,
			),
			'location'   => array(
				'type'    => 'string',
				'enum'    => array_keys( self::LOCATIONS ),
				'default' => 'head',
			),
			'priority'   => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 10,
				'default' => 1,
			),
			'conditions' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
			'dryRun'     => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'code' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	}

	protected function run( array $input ) {
		$snippet_id = (int) ( $input['snippetId'] ?? 0 );
		if ( $snippet_id && 'elementor_snippet' !== get_post_type( $snippet_id ) ) {
			return Errors::make( 'snippet_not_found', __( 'That ID is not an Elementor custom code snippet.', 'amcp-builder' ), '', 404 );
		}
		try {
			$conditions = isset( $input['conditions'] ) && Templates::has_theme_builder() ? Templates::parse_conditions( $input['conditions'] ) : null;
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$result             = array(
			'location' => $input['location'],
			'priority' => $input['priority'],
			'warnings' => array( null === $conditions ? 'Without conditions the snippet prints on every page once published.' : '' ),
		);
		$result['warnings'] = array_values( array_filter( $result['warnings'] ) );
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		if ( $snippet_id ) {
			$snapshot = $this->snapshots->take_post( $snippet_id, $this->name(), $this->label() );
			if ( isset( $input['title'] ) ) {
				wp_update_post(
					array(
						'ID'         => $snippet_id,
						'post_title' => sanitize_text_field( $input['title'] ),
					)
				);
			}
		} else {
			$snapshot   = 0;
			$snippet_id = wp_insert_post(
				array(
					'post_type'   => 'elementor_snippet',
					'post_status' => 'draft',
					'post_title'  => sanitize_text_field( $input['title'] ?? __( 'AMCP Builder snippet', 'amcp-builder' ) ),
				),
				true
			);
			if ( is_wp_error( $snippet_id ) ) {
				return Errors::make( 'create_failed', $snippet_id->get_error_message(), '', 500 );
			}
		}

		// VERIFY: Elementor Pro 4.3 custom-code metabox meta keys and conditions cache.
		update_post_meta( $snippet_id, '_elementor_location', self::LOCATIONS[ $input['location'] ] );
		update_post_meta( $snippet_id, '_elementor_priority', (int) $input['priority'] );
		update_post_meta( $snippet_id, '_elementor_code', wp_slash( (string) $input['code'] ) );
		if ( null !== $conditions ) {
			Templates::conditions_manager()->save_conditions( $snippet_id, $conditions );
		} elseif ( Templates::has_theme_builder() ) {
			Templates::conditions_manager()->get_cache()->regenerate();
		}

		return array(
			'dryRun'     => false,
			'snippetId'  => $snippet_id,
			'status'     => get_post_status( $snippet_id ),
			'snapshotId' => $snapshot,
		) + $result;
	}
}
