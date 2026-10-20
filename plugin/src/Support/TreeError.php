<?php
/**
 * Error raised by element tree operations.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Support;

use RuntimeException;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Carries a machine code and a fix hint, converted to a WP_Error by tools.
 */
final class TreeError extends RuntimeException {

	public function __construct( public readonly string $error_code, string $message, public readonly string $fix = '' ) {
		parent::__construct( $message );
	}

	public function to_wp_error(): WP_Error {
		return Errors::make( $this->error_code, $this->getMessage(), $this->fix );
	}
}
