<?php
/**
 * The bricks-upsert-variables tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

use AmcpBuilder\Bricks\DesignTokens;
use AmcpBuilder\Bricks\Ids;
use AmcpBuilder\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Adds or updates Bricks global CSS variables.
 */
final class UpsertVariables extends BricksAbility {

	public function slug(): string {
		return 'bricks-upsert-variables';
	}

	public function label(): string {
		return __( 'Bricks: add or update CSS variables', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Adds Bricks global CSS variables, or updates variables with the same name (for spacing, sizes, radii, fonts). Use them as "var(--name)" in any setting. '
			. 'Names are stored without the leading "--". Check bricks-get-design-tokens first: many sites already have a full scale (for example from Automatic.css). Variables are site-wide; a snapshot is taken first.';
	}

	protected function properties(): array {
		return array(
			'variables' => array(
				'type'     => 'array',
				'minItems' => 1,
				'maxItems' => 50,
				'items'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'     => array(
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 60,
						),
						'value'    => array(
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 300,
						),
						'category' => array(
							'type'      => 'string',
							'maxLength' => 60,
						),
					),
					'required'             => array( 'name', 'value' ),
					'additionalProperties' => false,
				),
			),
			'dryRun'    => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'variables' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$variables  = DesignTokens::variables();
		$categories = DesignTokens::categories();
		$ids        = new Ids( array_merge( array_column( $variables, 'id' ), array_column( $categories, 'id' ) ) );
		$by_name    = array_column( $categories, 'id', 'name' );
		$new_cats   = false;
		$changed    = array();

		foreach ( $input['variables'] as $variable ) {
			$name = DesignTokens::variable_name( (string) $variable['name'] );
			if ( '' === $name ) {
				return Errors::make( 'invalid_variable', __( 'A variable name must contain letters or numbers.', 'amcp-builder' ) );
			}
			// Values are CSS: strip anything that could close a style block.
			$value = trim( str_replace( array( '<', '>', '{', '}', ';' ), '', (string) $variable['value'] ) );
			$item  = array(
				'name'  => $name,
				'value' => $value,
			);
			if ( ! empty( $variable['category'] ) ) {
				$category = sanitize_text_field( $variable['category'] );
				if ( ! isset( $by_name[ $category ] ) ) {
					$by_name[ $category ] = $ids->next();
					$categories[]         = array(
						'id'   => $by_name[ $category ],
						'name' => $category,
					);
					$new_cats             = true;
				}
				$item['category'] = $by_name[ $category ];
			}

			$found = false;
			foreach ( $variables as $k => $existing ) {
				if ( ( $existing['name'] ?? '' ) === $name ) {
					$variables[ $k ] = $item + $existing;
					$found           = true;
				}
			}
			if ( ! $found ) {
				$variables[] = array( 'id' => $ids->next() ) + $item;
			}
			$changed[] = array(
				'name' => $name,
				'use'  => 'var(--' . $name . ')',
			);
		}

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'    => true,
				'variables' => $changed,
			);
		}

		$snapshot = $this->snapshots->take_option( DesignTokens::VARIABLES, $this->name(), $this->label() );
		if ( $new_cats ) {
			$this->snapshots->take_option( DesignTokens::CATEGORIES, $this->name(), $this->label() );
			update_option( DesignTokens::CATEGORIES, $categories );
		}
		update_option( DesignTokens::VARIABLES, $variables );
		DesignTokens::regenerate_css();

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
			'variables'  => $changed,
		);
	}
}
