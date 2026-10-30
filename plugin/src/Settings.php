<?php
/**
 * Plugin settings store.
 *
 * @package AcMcp
 */

namespace AcMcp;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, sanitizes and saves the single settings option.
 */
final class Settings {

	public const OPTION = 'ac_mcp_settings';

	/**
	 * Every setting with its default. The type of each default is also the
	 * type the sanitizer enforces.
	 */
	public const DEFAULTS = array(
		'kill_switch'         => false,
		'read_only'           => false,
		'dry_run_default'     => false,
		'custom_code_enabled' => false,
		'disabled_tools'      => array(),
		'rate_limit_reads'    => 120,
		'rate_limit_writes'   => 30,
		'snapshot_keep'       => 20,
		'snapshot_days'       => 30,
		'audit_days'          => 90,
		'post_lock'           => 'deny',
		'design_guide'        => true,
		'oauth_enabled'       => true,
		'design_guidelines'   => '',
	);

	/** Free-text settings and their maximum length. */
	private const TEXT = array(
		'design_guidelines' => 20000,
	);

	private const CHOICES = array(
		'post_lock' => array( 'deny', 'allow' ),
	);

	private const RANGES = array(
		'rate_limit_reads'  => array( 1, 10000 ),
		'rate_limit_writes' => array( 1, 10000 ),
		'snapshot_keep'     => array( 1, 500 ),
		'snapshot_days'     => array( 1, 3650 ),
		'audit_days'        => array( 1, 3650 ),
	);

	/**
	 * Returns all settings merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		return $this->sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Returns one setting.
	 *
	 * @param string $key Setting key from DEFAULTS.
	 * @return mixed
	 */
	public function get( string $key ) {
		return $this->all()[ $key ] ?? null;
	}

	/**
	 * Merges a partial update into the stored settings and saves them.
	 *
	 * @param array<string, mixed> $patch Keys to change.
	 * @return array<string, mixed> The saved settings.
	 */
	public function update( array $patch ): array {
		$settings = $this->sanitize( array_merge( $this->all(), $patch ) );
		update_option( self::OPTION, $settings );
		return $settings;
	}

	/**
	 * True when every AC MCP for Bricks and Elementor tool must refuse to run.
	 */
	public function is_killed(): bool {
		return ( defined( 'AC_MCP_DISABLE' ) && AC_MCP_DISABLE ) || $this->get( 'kill_switch' );
	}

	/**
	 * Coerces input into the shape of DEFAULTS, dropping unknown keys.
	 *
	 * @param array<string, mixed> $input Raw values.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $input ): array {
		$clean = array();
		foreach ( self::DEFAULTS as $key => $default ) {
			$value = $input[ $key ] ?? $default;

			if ( is_bool( $default ) ) {
				$clean[ $key ] = rest_sanitize_boolean( $value );
			} elseif ( is_int( $default ) ) {
				[ $min, $max ] = self::RANGES[ $key ];
				$clean[ $key ] = is_numeric( $value ) ? max( $min, min( $max, (int) $value ) ) : $default;
			} elseif ( is_array( $default ) ) {
				$clean[ $key ] = is_array( $value )
					? array_values( array_unique( array_filter( array_map( array( $this, 'sanitize_tool_name' ), $value ) ) ) )
					: $default;
			} elseif ( isset( self::TEXT[ $key ] ) ) {
				$clean[ $key ] = mb_substr( sanitize_textarea_field( (string) $value ), 0, self::TEXT[ $key ] );
			} else {
				$value         = sanitize_key( (string) $value );
				$clean[ $key ] = in_array( $value, self::CHOICES[ $key ], true ) ? $value : $default;
			}
		}
		return $clean;
	}

	/**
	 * Keeps only characters valid in an ability name ("namespace/name").
	 *
	 * @param mixed $name Raw value.
	 */
	private function sanitize_tool_name( $name ): string {
		return is_string( $name ) ? (string) preg_replace( '/[^a-z0-9\/-]/', '', strtolower( $name ) ) : '';
	}
}
