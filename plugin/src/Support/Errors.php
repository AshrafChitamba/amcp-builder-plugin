<?php
/**
 * Actionable error builder.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Support;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Builds WP_Errors whose message tells the AI what went wrong and how to fix it.
 *
 * The MCP Adapter only forwards the error message to the client (error data
 * is dropped), so the fix is part of the message text.
 */
final class Errors {

	/**
	 * @param string $code    Machine-readable code, e.g. "post_not_found".
	 * @param string $message What went wrong.
	 * @param string $fix     What the caller should do next. Optional.
	 * @param int    $status  HTTP status for REST callers.
	 */
	public static function make( string $code, string $message, string $fix = '', int $status = 400 ): WP_Error {
		$text = '' === $fix
			? $message
			/* translators: 1: error message, 2: suggested fix. */
			: sprintf( __( '%1$s How to fix: %2$s', 'mcp-builder' ), $message, $fix );

		return new WP_Error( 'mcp_builder_' . $code, $text, array( 'status' => $status ) );
	}
}
