<?php
/**
 * The bricks-update-theme-style tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

use AmcpBuilder\Bricks\DesignTokens;
use AmcpBuilder\Bricks\Ids;
use AmcpBuilder\Bricks\Templates;
use AmcpBuilder\Support\Errors;
use AmcpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Changes or creates a Bricks theme style (site-wide element defaults).
 */
final class UpdateThemeStyle extends BricksAbility {

	public function slug(): string {
		return 'bricks-update-theme-style';
	}

	public function label(): string {
		return __( 'Bricks: update theme style', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Changes a Bricks theme style: site-wide defaults grouped by area, e.g. {"typography":{"typographyHeadings":{"font-weight":"700"}},"colors":{"colorPrimary":{"hex":"#105903"}},"button":{...}}. '
			. 'Groups include general, colors, typography, links, and one per element (button, heading, container, section, block, div, text, form). A key set to null is removed. '
			. 'Pass styleId to edit a style (see bricks-get-design-tokens) or label to create one; a style only applies when it has conditions, e.g. [{"main":"any"}]. '
			. 'This changes every page using the style: only do it when the user asks for site-wide changes. A snapshot is taken first.';
	}

	protected function properties(): array {
		return array(
			'styleId'    => array(
				'type'      => 'string',
				'maxLength' => 60,
			),
			'label'      => array(
				'type'      => 'string',
				'maxLength' => 60,
			),
			'settings'   => array(
				'type'        => 'object',
				'description' => 'Groups of settings to merge: {"group":{"key":value}}.',
			),
			'conditions' => CreateTemplate::conditions_property(),
			'dryRun'     => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'settings' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$styles = DesignTokens::theme_styles();
		$id     = (string) ( $input['styleId'] ?? '' );
		if ( '' !== $id && ! isset( $styles[ $id ] ) ) {
			return Errors::make( 'style_not_found', __( 'That theme style does not exist.', 'amcp-builder' ), __( 'Call bricks-get-design-tokens for style IDs, or pass label to create a new style.', 'amcp-builder' ), 404 );
		}
		if ( '' === $id ) {
			if ( empty( $input['label'] ) ) {
				return Errors::make( 'missing_style', __( 'Pass styleId to edit a style or label to create one.', 'amcp-builder' ) );
			}
			$id            = ( new Ids( array_keys( $styles ) ) )->next();
			$styles[ $id ] = array(
				'label'    => sanitize_text_field( $input['label'] ),
				'settings' => array(),
			);
		}

		$settings = (array) ( $styles[ $id ]['settings'] ?? array() );
		foreach ( (array) $input['settings'] as $group => $values ) {
			if ( ! is_string( $group ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $group ) || 'conditions' === $group || ! is_array( $values ) ) {
				return Errors::make( 'invalid_group', sprintf( /* translators: %s: group name. */ __( 'Invalid settings group "%s".', 'amcp-builder' ), (string) $group ), __( 'Use groups such as general, colors, typography, links, button or heading, each with an object of settings.', 'amcp-builder' ) );
			}
			$current = (array) ( $settings[ $group ] ?? array() );
			foreach ( $values as $key => $value ) {
				if ( null === $value ) {
					unset( $current[ $key ] );
				} else {
					$current[ (string) $key ] = $value;
				}
			}
			if ( $current ) {
				$settings[ $group ] = $current;
			} else {
				unset( $settings[ $group ] );
			}
		}

		if ( isset( $input['conditions'] ) ) {
			try {
				$settings['conditions'] = array( 'conditions' => Templates::conditions( $input['conditions'], new Ids() ) );
			} catch ( TreeError $error ) {
				return $error->to_wp_error();
			}
		}
		$styles[ $id ]['settings'] = $settings;

		$result = array(
			'styleId'  => $id,
			'settings' => $settings,
			'warnings' => empty( $settings['conditions']['conditions'] ) ? array( 'This style has no conditions, so Bricks does not apply it anywhere yet.' ) : array(),
		);
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot = $this->snapshots->take_option( DesignTokens::STYLES, $this->name(), $this->label() );
		update_option( DesignTokens::STYLES, $styles );

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
