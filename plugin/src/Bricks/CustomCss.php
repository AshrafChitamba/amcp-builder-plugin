<?php
/**
 * Custom CSS selector placeholder handling.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * The Bricks builder shows "%root%" in custom CSS fields but stores the real
 * selector: "#brxe-{id}" for elements, ".{class-name}" for global classes.
 * The frontend prints stored CSS as is, so the placeholder must be resolved
 * before saving. VERIFY: Bricks 2.3.1 builder (main.min.js) replaces %root% on save.
 */
final class CustomCss {

	public static function element_selector( string $id ): string {
		return '#brxe-' . $id;
	}

	public static function class_selector( string $name ): string {
		return '.' . $name;
	}

	/**
	 * Replaces %root% in every _cssCustom setting (including breakpoint variants).
	 *
	 * @param array<string, mixed> $settings Element or class settings.
	 * @return array<string, mixed>
	 */
	public static function resolve( array $settings, string $selector ): array {
		foreach ( $settings as $key => $value ) {
			if ( is_string( $value ) && str_starts_with( (string) $key, '_cssCustom' ) ) {
				$settings[ $key ] = str_replace( '%root%', $selector, $value );
			}
		}
		return $settings;
	}
}
