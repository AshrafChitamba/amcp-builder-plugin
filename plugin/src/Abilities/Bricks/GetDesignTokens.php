<?php
/**
 * The bricks-get-design-tokens tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

use McpBuilder\Bricks\DesignTokens;
use McpBuilder\Bricks\GlobalClasses;
use McpBuilder\Support\Untrusted;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the site's colors, CSS variables and theme styles.
 */
final class GetDesignTokens extends BricksAbility {

	public function slug(): string {
		return 'bricks-get-design-tokens';
	}

	public function label(): string {
		return __( 'Bricks: get design tokens', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Returns the site\'s design system in Bricks: color palettes (with the CSS variable to use, e.g. {"raw":"var(--primary)"}), global CSS variables (use as "var(--name)" in any setting), '
			. 'theme styles (site-wide defaults per element) and the number of global classes. Call it before designing so pages reuse the site\'s colors, spacing and type scale instead of hard-coded values. '
			. 'Sites using a framework such as Automatic.css expose many variables here.';
	}

	protected function properties(): array {
		return array(
			'variableSearch' => array(
				'type'        => 'string',
				'maxLength'   => 60,
				'description' => 'Only variables whose name contains this text (sites can have hundreds).',
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		$search = strtolower( (string) ( $input['variableSearch'] ?? '' ) );

		$palettes = array();
		foreach ( DesignTokens::palettes() as $palette ) {
			$colors = array();
			foreach ( (array) ( $palette['colors'] ?? array() ) as $color ) {
				$colors[] = array_filter(
					array(
						'id'    => (string) ( $color['id'] ?? '' ),
						'name'  => Untrusted::text( $color['name'] ?? '', 60 ),
						'raw'   => (string) ( $color['raw'] ?? '' ),
						'light' => (string) ( $color['light'] ?? ( $color['hex'] ?? '' ) ),
						'dark'  => (string) ( $color['dark'] ?? '' ),
					),
					static fn( $value ): bool => '' !== $value
				);
			}
			$palettes[] = array(
				'id'     => (string) ( $palette['id'] ?? '' ),
				'name'   => Untrusted::text( $palette['name'] ?? '', 60 ),
				'colors' => $colors,
			);
		}

		$categories = array_column( DesignTokens::categories(), 'name', 'id' );
		$variables  = array();
		foreach ( DesignTokens::variables() as $variable ) {
			$name = (string) ( $variable['name'] ?? '' );
			if ( '' !== $search && ! str_contains( strtolower( $name ), $search ) ) {
				continue;
			}
			$variables[] = array(
				'name'     => $name,
				'use'      => 'var(--' . $name . ')',
				'value'    => Untrusted::text( $variable['value'] ?? '', 120 ),
				'category' => (string) ( $categories[ $variable['category'] ?? '' ] ?? '' ),
			);
		}

		$styles = array();
		foreach ( DesignTokens::theme_styles() as $id => $style ) {
			$settings = (array) ( $style['settings'] ?? array() );
			$styles[] = array(
				'id'            => (string) $id,
				'label'         => Untrusted::text( $style['label'] ?? $id, 60 ),
				'hasConditions' => ! empty( $settings['conditions']['conditions'] ),
				'groups'        => array_map( static fn( $group ): array => array_keys( (array) $group ), array_diff_key( $settings, array( 'conditions' => true ) ) ),
			);
		}

		return array(
			'palettes'         => $palettes,
			'variables'        => array_slice( $variables, 0, 300 ),
			'variableCount'    => count( $variables ),
			'themeStyles'      => $styles,
			'globalClassCount' => count( GlobalClasses::all() ),
			'notice'           => Untrusted::NOTICE,
		);
	}
}
