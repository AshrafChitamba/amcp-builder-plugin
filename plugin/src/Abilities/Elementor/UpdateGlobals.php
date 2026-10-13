<?php
/**
 * The elementor-update-globals tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Elementor\Ids;
use McpBuilder\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Adds or updates the kit's global colors and fonts.
 */
final class UpdateGlobals extends ElementorAbility {

	private const TYPOGRAPHY_FIELDS = array( 'font_family', 'font_weight', 'font_size', 'line_height', 'letter_spacing', 'text_transform', 'font_style', 'text_decoration' );

	public function slug(): string {
		return 'elementor-update-globals';
	}

	public function label(): string {
		return __( 'Elementor: update global colors and fonts', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Adds or updates global colors and typography in the active Elementor kit (Site Settings). Match an existing item by id (from elementor-get-globals, e.g. "primary") or add a new one by title. '
			. 'Typography fields: font_family, font_weight, font_size ({"unit":"px","size":18}), line_height ({"unit":"em","size":1.5}), letter_spacing, text_transform, font_style, text_decoration. '
			. 'Globals are site-wide: only change them when the user asks. A snapshot of the kit is taken first.';
	}

	protected function properties(): array {
		return array(
			'colors'     => array(
				'type'     => 'array',
				'maxItems' => 30,
				'items'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'    => array( 'type' => 'string' ),
						'title' => array(
							'type'      => 'string',
							'maxLength' => 60,
						),
						'color' => array(
							'type'    => 'string',
							'pattern' => '^(#[0-9a-fA-F]{3,8}|rgba?\\(.+\\)|hsla?\\(.+\\))$',
						),
					),
					'required'             => array( 'color' ),
					'additionalProperties' => false,
				),
			),
			'typography' => array(
				'type'     => 'array',
				'maxItems' => 30,
				'items'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'              => array( 'type' => 'string' ),
						'title'           => array(
							'type'      => 'string',
							'maxLength' => 60,
						),
						'font_family'     => array( 'type' => 'string' ),
						'font_weight'     => array( 'type' => array( 'string', 'integer' ) ),
						'font_size'       => self::size_property(),
						'line_height'     => self::size_property(),
						'letter_spacing'  => self::size_property(),
						'text_transform'  => array( 'type' => 'string' ),
						'font_style'      => array( 'type' => 'string' ),
						'text_decoration' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
			),
			'dryRun'     => self::dry_run_property(),
		);
	}

	/**
	 * Schema of an Elementor size value, e.g. {"unit":"px","size":18}.
	 *
	 * @return array<string, mixed>
	 */
	private static function size_property(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'unit' => array( 'type' => 'string' ),
				'size' => array( 'type' => array( 'number', 'string' ) ),
			),
			'required'             => array( 'size' ),
			'additionalProperties' => false,
		);
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		// Elementor's kit document requires edit_theme_options.
		return current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! $kit->get_id() ) {
			return Errors::make( 'no_kit', __( 'This site has no active Elementor kit.', 'mcp-builder' ), __( 'Open Elementor → Site Settings once to create it.', 'mcp-builder' ) );
		}

		// Start from the raw stored settings: get_settings() would add every control default.
		$settings = $kit->get_meta( '_elementor_page_settings' );
		$settings = is_array( $settings ) ? $settings : array();
		// A new kit stores nothing until Site Settings are saved; Elementor then shows the
		// default system colors and fonts. Seed them so ids such as "primary" update in place.
		foreach ( array( 'system_colors', 'system_typography' ) as $list ) {
			if ( ! isset( $settings[ $list ] ) ) {
				$defaults = $kit->get_settings( $list );
				if ( is_array( $defaults ) && $defaults ) {
					$settings[ $list ] = $defaults;
				}
			}
		}
		$ids     = new Ids();
		$changed = array();

		foreach ( (array) ( $input['colors'] ?? array() ) as $color ) {
			$item      = array_filter(
				array(
					'title' => isset( $color['title'] ) ? sanitize_text_field( $color['title'] ) : null,
					'color' => sanitize_text_field( $color['color'] ),
				),
				static fn( $value ): bool => null !== $value
			);
			$changed[] = 'color ' . $this->upsert( $settings, array( 'system_colors', 'custom_colors' ), $color, $item, $ids );
		}

		foreach ( (array) ( $input['typography'] ?? array() ) as $font ) {
			$item = array( 'typography_typography' => 'custom' );
			if ( isset( $font['title'] ) ) {
				$item['title'] = sanitize_text_field( $font['title'] );
			}
			foreach ( self::TYPOGRAPHY_FIELDS as $field ) {
				if ( isset( $font[ $field ] ) ) {
					$item[ 'typography_' . $field ] = is_array( $font[ $field ] ) ? array_map( 'sanitize_text_field', array_map( 'strval', $font[ $field ] ) ) : sanitize_text_field( (string) $font[ $field ] );
				}
			}
			$changed[] = 'typography ' . $this->upsert( $settings, array( 'system_typography', 'custom_typography' ), $font, $item, $ids );
		}

		if ( ! $changed ) {
			return Errors::make( 'nothing_to_change', __( 'Pass colors or typography to change.', 'mcp-builder' ) );
		}
		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'  => true,
				'changed' => $changed,
			);
		}

		$snapshot = $this->snapshots->take_post( $kit->get_id(), $this->name(), $this->label() );
		// Kit::save() saves the full settings and clears Elementor's CSS cache.
		if ( false === $kit->save( array( 'settings' => $settings ) ) ) {
			return Errors::make( 'kit_save_failed', __( 'Elementor refused to save the kit.', 'mcp-builder' ), '', 500 );
		}

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
			'changed'    => $changed,
		);
	}

	/**
	 * Updates the item with the given id or title in either repeater, or adds a custom item.
	 *
	 * @param array<string, mixed> $settings Kit settings (changed in place).
	 * @param list<string>         $lists    System and custom repeater keys.
	 * @param array<string, mixed> $input    Requested item.
	 * @param array<string, mixed> $item     Sanitized fields to set.
	 * @return string The item's global id.
	 */
	private function upsert( array &$settings, array $lists, array $input, array $item, Ids $ids ): string {
		foreach ( $lists as $list ) {
			foreach ( (array) ( $settings[ $list ] ?? array() ) as $index => $existing ) {
				$matches = ( ! empty( $input['id'] ) && ( $existing['_id'] ?? '' ) === $input['id'] )
					|| ( empty( $input['id'] ) && ! empty( $item['title'] ) && ( $existing['title'] ?? '' ) === $item['title'] );
				if ( $matches ) {
					$settings[ $list ][ $index ] = $item + $existing;
					return (string) $existing['_id'];
				}
			}
		}
		$id                      = ! empty( $input['id'] ) ? sanitize_key( $input['id'] ) : $ids->next();
		$settings[ $lists[1] ][] = array( '_id' => $id ) + $item + array( 'title' => $id );
		return $id;
	}
}
