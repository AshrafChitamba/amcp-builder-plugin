<?php
/**
 * Applies the safety rules to every managed ability.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Safety;

use McpBuilder\Abilities\Registry;
use McpBuilder\Settings;
use McpBuilder\Support\Errors;
use WP_Ability;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WordPress core's ability execution, so the rules apply to
 * MCP Builder's tools and to the official Bricks and Elementor abilities,
 * whichever server or REST route calls them.
 *
 * - wp_pre_execute_ability: kill switch, disabled tools, custom-code gate,
 *   read-only mode, editor locks, rate limits. Denials are audited.
 * - wp_before_execute_ability: snapshot before official abilities write.
 *   MCP Builder's own tools snapshot themselves, after a dry-run check.
 * - wp_ability_execute_result: audit the outcome (success or error).
 * - mcp_adapter_tools_list: hide tools that would be refused anyway.
 */
class Governance {

	public const MANAGED_NAMESPACES = array( 'mcp-builder/', 'bricks/', 'elementor/' );

	/** Official abilities that run arbitrary code. */
	public const CUSTOM_CODE_ABILITIES = array( 'bricks/execute-php' );

	/** @var array<string, array{start: float, snapshot: int}> */
	private array $calls = array();

	public function __construct(
		private Settings $settings,
		private Registry $registry,
		private AuditLog $audit,
		private Snapshots $snapshots,
		private RateLimiter $rate_limiter
	) {}

	public function register(): void {
		add_filter( 'wp_pre_execute_ability', array( $this, 'pre_execute' ), 10, 4 );
		add_action( 'wp_before_execute_ability', array( $this, 'before_execute' ), 10, 3 );
		add_filter( 'wp_ability_execute_result', array( $this, 'after_execute' ), 10, 4 );
		add_filter( 'mcp_adapter_tools_list', array( $this, 'filter_tools_list' ) );
	}

	public static function is_managed( string $name ): bool {
		foreach ( self::MANAGED_NAMESPACES as $prefix ) {
			if ( str_starts_with( $name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	public static function is_readonly( WP_Ability $ability ): bool {
		return true === ( $ability->get_meta()['annotations']['readonly'] ?? null );
	}

	public function is_custom_code( string $name ): bool {
		if ( in_array( $name, self::CUSTOM_CODE_ABILITIES, true ) ) {
			return true;
		}
		$own = $this->registry->find( $name );
		return $own && 'custom-code' === $own->group();
	}

	/**
	 * @param mixed      $pre     Sentinel, or a value from an earlier filter.
	 * @param string     $name    Ability name.
	 * @param mixed      $input   Raw input.
	 * @param WP_Ability $ability Ability.
	 * @return mixed
	 */
	public function pre_execute( $pre, string $name, $input, WP_Ability $ability ) {
		if ( ! self::is_managed( $name ) || $pre instanceof WP_Error ) {
			return $pre;
		}

		$denied = $this->check( $name, $input, $ability );
		if ( $denied ) {
			$this->audit->record(
				array(
					'ability' => $name,
					'outcome' => 'denied',
					'post_id' => Targets::post_id( $input ),
					'message' => $denied->get_error_message(),
					'input'   => $input,
				)
			);
			return $denied;
		}

		$this->calls[ $name ] = array(
			'start'    => microtime( true ),
			'snapshot' => 0,
		);
		return $pre;
	}

	/**
	 * @param mixed $input Validated input.
	 */
	public function before_execute( string $name, $input, WP_Ability $ability ): void {
		if ( ! self::is_managed( $name ) || str_starts_with( $name, 'mcp-builder/' ) || self::is_readonly( $ability ) ) {
			return;
		}

		$post_id = Targets::post_id( $input );
		$option  = Targets::option_for( $name );
		$label   = __( 'Before an official builder tool wrote', 'mcp-builder' );

		$snapshot = 0;
		if ( $post_id && get_post( $post_id ) ) {
			$snapshot = $this->snapshots->take_post( $post_id, $name, $label );
		} elseif ( $option ) {
			$snapshot = $this->snapshots->take_option( $option, $name, $label );
		}

		if ( isset( $this->calls[ $name ] ) ) {
			$this->calls[ $name ]['snapshot'] = $snapshot;
		}
	}

	/**
	 * @param mixed $result Execution result.
	 * @param mixed $input  Validated input.
	 * @return mixed Unchanged result.
	 */
	public function after_execute( $result, string $name, $input, WP_Ability $ability ) {
		if ( ! self::is_managed( $name ) ) {
			return $result;
		}

		$call = $this->calls[ $name ] ?? array(
			'start'    => microtime( true ),
			'snapshot' => 0,
		);
		unset( $this->calls[ $name ] );

		$is_error = $result instanceof WP_Error;
		$data     = is_array( $result ) ? $result : array();

		if ( $is_error ) {
			$outcome = 'error';
		} elseif ( ! empty( $data['dryRun'] ) ) {
			$outcome = 'dry-run';
		} else {
			$outcome = 'ok';
		}

		// Reads are only audited when they fail, to keep the log useful.
		if ( 'ok' === $outcome && self::is_readonly( $ability ) ) {
			return $result;
		}

		$this->audit->record(
			array(
				'ability'     => $name,
				'outcome'     => $outcome,
				'post_id'     => Targets::post_id( $input ),
				'snapshot_id' => (int) ( $data['snapshotId'] ?? $call['snapshot'] ),
				'duration_ms' => (int) round( ( microtime( true ) - $call['start'] ) * 1000 ),
				'message'     => $is_error ? $result->get_error_message() : '',
				'input'       => $input,
			)
		);

		return $result;
	}

	/**
	 * Hides tools that are disabled, or writes while in read-only mode.
	 *
	 * @param array<int, object> $tools \WP\McpSchema\Record\Tool records.
	 * @return array<int, object>
	 */
	public function filter_tools_list( $tools ): array {
		if ( ! is_array( $tools ) ) {
			return array();
		}
		return array_values(
			array_filter(
				$tools,
				function ( $tool ): bool {
					if ( ! is_object( $tool ) || ! method_exists( $tool, 'getName' ) ) {
						return true;
					}
					$ability = $this->ability_for_tool( $tool->getName() );
					return ! $ability || ! $this->check( $ability->get_name(), null, $ability, false );
				}
			)
		);
	}

	/**
	 * Returns a WP_Error when the call must be refused.
	 *
	 * @param mixed $input        Raw input.
	 * @param bool  $count_toward Whether this call counts toward rate limits.
	 */
	public function check( string $name, $input, WP_Ability $ability, bool $count_toward = true ): ?WP_Error {
		$settings = $this->settings->all();
		$readonly = self::is_readonly( $ability );

		if ( $this->settings->is_killed() ) {
			return Errors::make( 'disabled', __( 'MCP Builder is switched off by a site administrator (kill switch).', 'mcp-builder' ), __( 'Stop and tell the user; nothing can run until an admin turns it back on.', 'mcp-builder' ), 503 );
		}

		if ( in_array( $name, $settings['disabled_tools'], true ) ) {
			return Errors::make( 'tool_disabled', __( 'This tool is turned off in MCP Builder settings.', 'mcp-builder' ), __( 'Use another tool, or ask an admin to enable it under Settings → MCP Builder → Tools.', 'mcp-builder' ), 403 );
		}

		if ( $this->is_custom_code( $name ) && ( ! $settings['custom_code_enabled'] || ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) ) {
			return Errors::make( 'custom_code_disabled', __( 'Custom-code tools are disabled, or your user is not allowed to use them.', 'mcp-builder' ), __( 'Build with native elements instead. An administrator can enable custom code under Settings → MCP Builder → Tools.', 'mcp-builder' ), 403 );
		}

		if ( ! $readonly && $settings['read_only'] ) {
			return Errors::make( 'read_only', __( 'The site is in read-only mode, so no changes can be made.', 'mcp-builder' ), __( 'Only use read tools, or ask an admin to turn off read-only mode.', 'mcp-builder' ), 403 );
		}

		if ( ! $count_toward ) {
			return null;
		}

		if ( ! $readonly && 'deny' === $settings['post_lock'] && ! str_starts_with( $name, 'mcp-builder/' ) ) {
			$locked_by = self::locked_by( Targets::post_id( $input ) );
			if ( $locked_by ) {
				/* translators: %s: user name. */
				return Errors::make( 'post_locked', sprintf( __( '%s is editing this page right now.', 'mcp-builder' ), $locked_by ), __( 'Wait until they close the editor, then try again.', 'mcp-builder' ), 409 );
			}
		}

		$limit = $readonly ? $settings['rate_limit_reads'] : $settings['rate_limit_writes'];
		if ( ! $this->rate_limiter->hit( get_current_user_id(), $readonly ? 'read' : 'write', $limit ) ) {
			return Errors::make( 'rate_limited', __( 'Too many requests in the last minute.', 'mcp-builder' ), __( 'Wait a minute, then continue. Batch changes into fewer calls where the tool allows it.', 'mcp-builder' ), 429 );
		}

		return null;
	}

	/**
	 * Display name of another user editing the post, or null.
	 */
	public static function locked_by( int $post_id ): ?string {
		if ( ! $post_id ) {
			return null;
		}
		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$user_id = wp_check_post_lock( $post_id );
		if ( ! $user_id ) {
			return null;
		}
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : __( 'Another user', 'mcp-builder' );
	}

	private function ability_for_tool( string $tool_name ): ?WP_Ability {
		foreach ( wp_get_abilities() as $ability ) {
			if ( str_replace( '/', '-', $ability->get_name() ) === $tool_name ) {
				return self::is_managed( $ability->get_name() ) ? $ability : null;
			}
		}
		return null;
	}
}
