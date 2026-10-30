<?php
/**
 * The bricks-upsert-global-class tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Bricks\CustomCss;
use AcMcp\Bricks\GlobalClasses;
use AcMcp\Bricks\Ids;
use AcMcp\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a global class, or updates one with the same name.
 */
final class UpsertGlobalClass extends BricksAbility {

	public function slug(): string {
		return 'bricks-upsert-global-class';
	}

	public function label(): string {
		return __( 'Bricks: create or update global class', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Creates a Bricks global class, or updates the class with the same name. Settings use the same style keys as elements, all starting with "_" '
			. '(e.g. {"_padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"},"_border":{"radius":{"top":"12px","right":"12px","bottom":"12px","left":"12px"}}}). '
			. 'Responsive and state suffixes work too ("_padding:mobile_portrait"). In "_cssCustom", write %root% for the class selector; it is replaced with ".name" on save, as the Bricks builder does. mode "merge" (default) keeps other keys; a null value removes a key. '
			. 'Global classes affect every element using them across the site. A snapshot of all classes is taken first.';
	}

	protected function properties(): array {
		return array(
			'name'     => array(
				'type'        => 'string',
				'pattern'     => '^[a-zA-Z_][a-zA-Z0-9_-]{0,79}$',
				'description' => 'CSS class name without the dot, e.g. "card" or "btn--primary".',
			),
			'settings' => array( 'type' => 'object' ),
			'category' => array(
				'type'      => 'string',
				'maxLength' => 60,
			),
			'mode'     => array(
				'type'    => 'string',
				'enum'    => array( 'merge', 'replace' ),
				'default' => 'merge',
			),
			'dryRun'   => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'name', 'settings' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$settings = (array) $input['settings'];
		foreach ( array_keys( $settings ) as $key ) {
			if ( ! str_starts_with( (string) $key, '_' ) ) {
				return Errors::make(
					'invalid_class_setting',
					/* translators: %s: settings key. */
					sprintf( __( 'Global class settings must be style keys starting with "_"; "%s" is not.', 'ac-mcp-for-bricks-elementor' ), $key ),
					__( 'Use style keys from bricks-get-element-schema, e.g. _padding, _typography, _background.', 'ac-mcp-for-bricks-elementor' )
				);
			}
		}

		$existing = GlobalClasses::find( $input['name'] );
		$current  = $existing && 'merge' === $input['mode'] ? (array) ( $existing['settings'] ?? array() ) : array();
		foreach ( $settings as $key => $value ) {
			if ( null === $value ) {
				unset( $current[ $key ] );
			} else {
				$current[ $key ] = $value;
			}
		}

		// Same rule Bricks applies to element data: without unfiltered_html, no raw CSS and filtered strings.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			unset( $current['_cssCustom'] );
			array_walk_recursive(
				$current,
				static function ( &$value ): void {
					$value = is_string( $value ) ? wp_kses_post( $value ) : $value;
				}
			);
		}

		$current = CustomCss::resolve( $current, CustomCss::class_selector( $input['name'] ) );

		$class    = array(
			'id'       => $existing['id'] ?? ( new Ids( array_column( GlobalClasses::all(), 'id' ) ) )->next(),
			'name'     => $input['name'],
			'settings' => $current,
		);
		$category = $input['category'] ?? ( $existing['category'] ?? '' );
		if ( '' !== $category ) {
			$class['category'] = sanitize_text_field( $category );
		}

		$result = array(
			'id'      => $class['id'],
			'name'    => $class['name'],
			'created' => null === $existing,
		);

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'   => true,
				'settings' => $current,
			) + $result;
		}

		$snapshot = $this->snapshots->take_option( GlobalClasses::OPTION, $this->name(), $this->label() );
		GlobalClasses::save( $class + ( $existing ? $existing : array() ) );

		return array(
			'dryRun'     => false,
			'snapshotId' => $snapshot,
		) + $result;
	}
}
