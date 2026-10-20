<?php
/**
 * The bricks-update-page-settings tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

use AmcpBuilder\Safety\Governance;
use AmcpBuilder\Support\CustomCode;
use AmcpBuilder\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Changes Bricks page settings (header/footer visibility, SEO fields, page CSS).
 */
final class UpdatePageSettings extends BricksAbility {

	private const ALLOWED = array(
		'bodyClasses'        => 'string',
		'headerDisabled'     => 'boolean',
		'footerDisabled'     => 'boolean',
		'popupDisabled'      => 'boolean',
		'disableLazyLoad'    => 'boolean',
		'documentTitle'      => 'string',
		'metaDescription'    => 'string',
		'metaKeywords'       => 'string',
		'metaRobots'         => 'array',
		'sharingTitle'       => 'string',
		'sharingDescription' => 'string',
		'sharingImage'       => 'object',
		'customCss'          => 'string',
	);

	private const SCRIPTS = array( 'customScriptsHeader', 'customScriptsBodyHeader', 'customScriptsBodyFooter' );

	public function slug(): string {
		return 'bricks-update-page-settings';
	}

	public function label(): string {
		return __( 'Bricks: update page settings', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Changes Bricks page settings of a page or template: bodyClasses, headerDisabled, footerDisabled, popupDisabled, disableLazyLoad, documentTitle, metaDescription, metaKeywords, metaRobots, '
			. 'sharingTitle, sharingDescription, sharingImage ({"id":..,"url":..}), customCss (use "#brxe-ID" or body classes as selectors). A null value removes a setting. '
			. 'Page scripts (customScriptsHeader, customScriptsBodyHeader, customScriptsBodyFooter) are custom code and only work when custom code is allowed. A snapshot is taken first.';
	}

	protected function properties(): array {
		return array(
			'postId'   => self::post_id_property(),
			'settings' => array( 'type' => 'object' ),
			'dryRun'   => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'postId', 'settings' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function run( array $input ) {
		$loaded = $this->load( (int) $input['postId'] );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}
		$post = $loaded['post'];

		$locked_by = Governance::locked_by( $post->ID );
		if ( $locked_by && 'deny' === $this->settings->get( 'post_lock' ) ) {
			/* translators: %s: user name. */
			return Errors::make( 'post_locked', sprintf( __( '%s has this page open in an editor.', 'amcp-builder' ), $locked_by ), __( 'Ask the user to close the editor, then try again.', 'amcp-builder' ), 409 );
		}

		$current = get_post_meta( $post->ID, '_bricks_page_settings', true );
		$current = is_array( $current ) ? $current : array();
		foreach ( (array) $input['settings'] as $key => $value ) {
			$is_script = in_array( $key, self::SCRIPTS, true );
			if ( ! isset( self::ALLOWED[ $key ] ) && ! $is_script ) {
				$hint = __( 'Use one of: ', 'amcp-builder' ) . implode( ', ', array_merge( array_keys( self::ALLOWED ), self::SCRIPTS ) ) . '.';
				return Errors::make( 'unknown_setting', sprintf( /* translators: %s: key. */ __( 'Unknown page setting "%s".', 'amcp-builder' ), (string) $key ), $hint );
			}
			if ( $is_script && ! CustomCode::allowed( $this->settings ) ) {
				return CustomCode::refusal( 'Page scripts' )->to_wp_error();
			}
			if ( null === $value || false === $value ) {
				unset( $current[ $key ] );
			} else {
				$current[ $key ] = is_string( $value ) && 'customCss' !== $key && ! $is_script ? sanitize_text_field( $value ) : $value;
			}
		}

		$result = array(
			'postId'   => $post->ID,
			'settings' => $current,
		);
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		if ( $current ) {
			update_post_meta( $post->ID, '_bricks_page_settings', wp_slash( $current ) );
		} else {
			delete_post_meta( $post->ID, '_bricks_page_settings' );
		}
		wp_update_post( array( 'ID' => $post->ID ) );

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
