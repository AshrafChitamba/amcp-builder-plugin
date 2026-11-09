<?php
/**
 * Custom-code policy.
 *
 * @package AcMcp
 */

namespace AcMcp\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Raw HTML, JavaScript and PHP are not available through this directory build.
 */
final class CustomCode {

	public static function allowed(): bool {
		return false;
	}

	public static function refusal( string $what ): TreeError {
		return new TreeError(
			'custom_code_disabled',
			sprintf( 'This directory version does not allow custom code (%s).', $what ),
			'Build it with native elements instead. This directory version does not provide a custom-code option.'
		);
	}
}
