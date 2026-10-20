<?php
/**
 * The elementor-update-page-settings tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Elementor;

use AmcpBuilder\Elementor\Store;
use AmcpBuilder\Elementor\Validator;
use AmcpBuilder\Support\Errors;
use AmcpBuilder\Support\Posts;
use AmcpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Changes page-level Elementor settings such as the page template.
 */
final class UpdatePageSettings extends ElementorWriteAbility {

	/** Settings that change the post itself; they stay with WordPress and a human. */
	private const BLOCKED = array( 'post_status', 'post_title', 'post_excerpt', 'post_featured_image', 'menu_order', 'comment_status', 'id' );

	public function slug(): string {
		return 'elementor-update-page-settings';
	}

	public function label(): string {
		return __( 'Elementor: update page settings', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Changes the page-level settings of an Elementor page (the gear icon in the editor): template, hide_title, page background, padding and similar. '
			. 'Use template "elementor_canvas" for a blank page without the theme header and footer (good for landing pages that include their own), or "elementor_header_footer" for full width with the theme header and footer. '
			. 'Settings merge into the existing ones; a key set to null is removed. Title and status are not changed here. A snapshot is taken before saving.';
	}

	protected function change_properties(): array {
		return array(
			'settings' => array(
				'type'    => 'object',
				'default' => array(),
			),
		);
	}

	protected function change_required(): array {
		return array( 'settings' );
	}

	protected function run( array $input ) {
		$loaded = $this->load( (int) $input['postId'] );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}
		$post = $loaded['post'];

		$locked = $this->lock_error( $post->ID, (bool) $input['force'] );
		if ( $locked ) {
			return $locked;
		}

		$patch   = (array) $input['settings'];
		$blocked = array_intersect( array_keys( $patch ), self::BLOCKED );
		if ( $blocked ) {
			return Errors::make( 'setting_not_allowed', sprintf( /* translators: %s: setting keys. */ __( 'These settings change the post itself and cannot be set here: %s.', 'amcp-builder' ), implode( ', ', $blocked ) ), __( 'Leave publishing and titles to a person in WordPress.', 'amcp-builder' ) );
		}

		$document = Store::document( $post->ID );
		try {
			Validator::known_keys(
				array_filter( $patch, static fn( $value ): bool => null !== $value ),
				$this->catalog()->document_setting_keys( $document ),
				'this page',
				'Call elementor-get-page to see the current page settings. Common keys: template, hide_title, background_background, background_color, padding.'
			);
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$before   = Store::read_settings( $post->ID ) + array( 'template' => (string) get_post_meta( $post->ID, '_wp_page_template', true ) );
		$settings = $before;
		foreach ( $patch as $key => $value ) {
			if ( null === $value ) {
				unset( $settings[ $key ] );
			} else {
				$settings[ $key ] = $value;
			}
		}

		$changed = array_values(
			array_filter(
				array_unique( array_merge( array_keys( $before ), array_keys( $settings ) ) ),
				static fn( $key ): bool => ( $before[ $key ] ?? null ) != ( $settings[ $key ] ?? null ) // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- Structural comparison of nested arrays.
			)
		);
		$result  = array(
			'postId'      => $post->ID,
			'changedKeys' => $changed,
		);

		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot_id = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		try {
			$saved = Store::write( $post->ID, array( 'settings' => $settings ) );
		} catch ( \Exception $exception ) {
			return Errors::make( 'elementor_save_failed', sprintf( /* translators: %s: Elementor error message. */ __( 'Elementor refused the page settings: %s', 'amcp-builder' ), $exception->getMessage() ), '', 400 );
		}
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'dryRun'          => false,
			'snapshotId'      => $snapshot_id,
			'template'        => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'builderEditLink' => Posts::builder_edit_link( $post, 'elementor' ),
		) + $result;
	}

	protected function mutate( \AmcpBuilder\Elementor\Tree $tree, array $input, Validator $validator ): array {
		return array();
	}
}
