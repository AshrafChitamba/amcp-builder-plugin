<?php
/**
 * Per-user rate limiting.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Safety;

defined( 'ABSPATH' ) || exit;

/**
 * Counts calls per user in fixed one-minute windows. Best effort: it stops
 * runaway AI loops, it is not protection against attacks.
 */
class RateLimiter {

	/**
	 * Records one call and returns false when the limit is already reached.
	 *
	 * @param string $kind "read" or "write".
	 */
	public function hit( int $user_id, string $kind, int $limit ): bool {
		$key   = sprintf( 'mcpb_rl_%d_%s_%d', $user_id, $kind, (int) floor( time() / MINUTE_IN_SECONDS ) );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, 2 * MINUTE_IN_SECONDS );
		return true;
	}
}
