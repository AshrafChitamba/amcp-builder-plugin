<?php
/**
 * The bricks-upsert-colors tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\DesignTokens;
use AcMcp\Bricks\Ids;
use AcMcp\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Adds or updates colors in a Bricks color palette.
 */
final class UpsertColors extends BricksAbility {

	public function slug(): string {
		return 'bricks-upsert-colors';
	}

	public function label(): string {
		return __( 'Bricks: add or update colors', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Adds colors to a Bricks color palette, or updates colors with the same name. Each color becomes a CSS variable (--<variable>) usable as {"raw":"var(--<variable>)"} in color settings. '
			. 'Use only when the user wants new brand colors: prefer existing palette colors (bricks-get-design-tokens). Palette colors are site-wide. A snapshot is taken first.';
	}

	protected function properties(): array {
		return array(
			'palette' => array(
				'type'        => 'string',
				'maxLength'   => 60,
				'description' => 'Palette name or ID. Created if it does not exist. Defaults to the first palette.',
			),
			'colors'  => array(
				'type'     => 'array',
				'minItems' => 1,
				'maxItems' => 30,
				'items'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'     => array(
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 60,
						),
						'value'    => array(
							'type'        => 'string',
							'pattern'     => '^(#[0-9a-fA-F]{3,8}|rgba?\\(.+\\)|hsla?\\(.+\\)|oklch\\(.+\\))$',
							'description' => 'Hex, rgb(), hsl() or oklch() value.',
						),
						'variable' => array(
							'type'        => 'string',
							'maxLength'   => 60,
							'description' => 'CSS variable name without dashes. Defaults to the color name in kebab case.',
						),
					),
					'required'             => array( 'name', 'value' ),
					'additionalProperties' => false,
				),
			),
			'dryRun'  => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'colors' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$palettes = DesignTokens::palettes();
		$wanted   = (string) ( $input['palette'] ?? '' );
		$index    = '' === $wanted ? ( $palettes ? 0 : null ) : null;
		foreach ( $palettes as $i => $palette ) {
			if ( '' !== $wanted && ( ( $palette['id'] ?? '' ) === $wanted || ( $palette['name'] ?? '' ) === $wanted ) ) {
				$index = $i;
			}
		}
		$ids = new Ids( array_merge( array_column( $palettes, 'id' ), ...array_map( static fn( $p ) => array_column( (array) ( $p['colors'] ?? array() ), 'id' ), $palettes ) ) );
		if ( null === $index ) {
			$palettes[] = array(
				'id'     => $ids->next(),
				'name'   => '' !== $wanted ? sanitize_text_field( $wanted ) : 'AC MCP for Bricks and Elementor',
				'colors' => array(),
			);
			$index      = count( $palettes ) - 1;
		}

		$changed = array();
		foreach ( $input['colors'] as $color ) {
			$variable = DesignTokens::variable_name( (string) ( $color['variable'] ?? $color['name'] ) );
			if ( '' === $variable ) {
				return Errors::make( 'invalid_color', __( 'A color needs a name that can become a CSS variable.', 'ac-mcp-for-bricks-elementor' ) );
			}
			$item  = array(
				'name'  => sanitize_text_field( $color['name'] ),
				'raw'   => 'var(--' . $variable . ')',
				'light' => sanitize_text_field( $color['value'] ),
			);
			$found = false;
			foreach ( $palettes[ $index ]['colors'] as $k => $existing ) {
				if ( ( $existing['name'] ?? '' ) === $item['name'] ) {
					$palettes[ $index ]['colors'][ $k ] = $item + $existing;
					$found                              = true;
				}
			}
			if ( ! $found ) {
				$palettes[ $index ]['colors'][] = array( 'id' => $ids->next() ) + $item;
			}
			$changed[] = array(
				'name' => $item['name'],
				'use'  => array( 'raw' => $item['raw'] ),
			);
		}

		$result = array(
			'palette' => (string) $palettes[ $index ]['name'],
			'colors'  => $changed,
		);
		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot = $this->snapshots->take_option( DesignTokens::PALETTE, $this->name(), $this->label() );
		update_option( DesignTokens::PALETTE, $palettes );
		DesignTokens::regenerate_css();

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
