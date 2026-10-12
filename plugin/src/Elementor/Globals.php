<?php
/**
 * Elementor kit globals (site colors and fonts).
 *
 * @package McpBuilder
 */

namespace McpBuilder\Elementor;

use McpBuilder\Support\Untrusted;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the active kit's global colors and typography.
 *
 * VERIFY: Kits_Manager::get_active_kit() and the system_colors / custom_colors /
 * system_typography / custom_typography repeaters (Elementor 4.3.4 core/kits).
 */
final class Globals {

	/**
	 * @return array{colors: list<array<string, mixed>>, typography: list<array<string, mixed>>}
	 */
	public static function read(): array {
		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! method_exists( $kit, 'get_settings' ) ) {
			return array(
				'colors'     => array(),
				'typography' => array(),
			);
		}

		$colors = array();
		foreach ( array(
			'system_colors' => true,
			'custom_colors' => false,
		) as $key => $system ) {
			foreach ( (array) $kit->get_settings( $key ) as $item ) {
				if ( empty( $item['_id'] ) ) {
					continue;
				}
				$colors[] = array(
					'id'        => (string) $item['_id'],
					'title'     => Untrusted::text( $item['title'] ?? '', 60 ),
					'value'     => (string) ( $item['color'] ?? '' ),
					'system'    => $system,
					'reference' => 'globals/colors?id=' . $item['_id'],
				);
			}
		}

		$typography = array();
		foreach ( array(
			'system_typography' => true,
			'custom_typography' => false,
		) as $key => $system ) {
			foreach ( (array) $kit->get_settings( $key ) as $item ) {
				if ( empty( $item['_id'] ) ) {
					continue;
				}
				$values = array();
				foreach ( $item as $field => $value ) {
					if ( str_starts_with( (string) $field, 'typography_' ) && '' !== $value && array() !== $value && ! ( is_array( $value ) && '' === ( $value['size'] ?? null ) ) ) {
						$values[ substr( (string) $field, 11 ) ] = $value;
					}
				}
				$typography[] = array(
					'id'        => (string) $item['_id'],
					'title'     => Untrusted::text( $item['title'] ?? '', 60 ),
					'value'     => (object) $values,
					'system'    => $system,
					'reference' => 'globals/typography?id=' . $item['_id'],
				);
			}
		}

		return array(
			'colors'     => $colors,
			'typography' => $typography,
		);
	}
}
