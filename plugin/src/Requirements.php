<?php
/**
 * Minimum-version checks.
 *
 * @package McpBuilder
 */

namespace McpBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Compares the environment with the supported minimums.
 */
final class Requirements {

	public const MIN_WP        = '7.1';
	public const MIN_PHP       = '8.1';
	public const MIN_ADAPTER   = '0.7.0';
	public const MIN_BRICKS    = '2.0';
	public const MIN_ELEMENTOR = '3.25';

	public function __construct( private Environment $environment ) {}

	/**
	 * True when the core requirements are met and tools can be registered.
	 */
	public function can_run(): bool {
		foreach ( $this->core_checks() as $check ) {
			if ( 'error' === $check['status'] ) {
				return false;
			}
		}
		return true;
	}

	public function bricks_supported(): bool {
		$version = $this->environment->bricks_version();
		return null !== $version && version_compare( $version, self::MIN_BRICKS, '>=' );
	}

	public function elementor_supported(): bool {
		$version = $this->environment->elementor_version();
		return null !== $version && version_compare( $version, self::MIN_ELEMENTOR, '>=' );
	}

	/**
	 * Checks that block the plugin when they fail.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function core_checks(): array {
		$adapter = $this->environment->adapter_version();

		return array(
			$this->version_check( 'wordpress', __( 'WordPress', 'mcp-builder' ), $this->environment->wp_version(), self::MIN_WP, 'error' ),
			$this->version_check( 'php', __( 'PHP', 'mcp-builder' ), $this->environment->php_version(), self::MIN_PHP, 'error' ),
			null === $adapter
				? $this->check( 'mcpAdapter', __( 'MCP Adapter plugin', 'mcp-builder' ), 'error', null, self::MIN_ADAPTER, __( 'Install and activate the "MCP Adapter" plugin from Plugins → Add New.', 'mcp-builder' ) )
				: $this->version_check( 'mcpAdapter', __( 'MCP Adapter plugin', 'mcp-builder' ), $adapter, self::MIN_ADAPTER, 'error' ),
		);
	}

	/**
	 * Every check shown on the Status tab, including builders and server setup.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function all_checks(): array {
		$env    = $this->environment;
		$checks = $this->core_checks();

		$checks[] = $this->builder_check( 'bricks', __( 'Bricks', 'mcp-builder' ), $env->bricks_version(), self::MIN_BRICKS );
		$checks[] = $this->builder_check( 'elementor', __( 'Elementor', 'mcp-builder' ), $env->elementor_version(), self::MIN_ELEMENTOR );

		if ( $env->elementor_atomic_active() ) {
			$checks[] = $this->check( 'elementorAtomic', __( 'Elementor Atomic editor', 'mcp-builder' ), 'warning', __( 'On', 'mcp-builder' ), null, __( 'Pages built with Atomic (v4) elements can be read but not edited by MCP Builder yet. Classic widgets and Containers are fully supported.', 'mcp-builder' ) );
		}

		$checks[] = $env->is_https()
			? $this->check( 'https', __( 'HTTPS', 'mcp-builder' ), 'ok', __( 'Yes', 'mcp-builder' ) )
			: $this->check( 'https', __( 'HTTPS', 'mcp-builder' ), 'local' === wp_get_environment_type() ? 'warning' : 'error', __( 'No', 'mcp-builder' ), null, __( 'Application Passwords are sent in every request. Use HTTPS on any site reachable from the internet.', 'mcp-builder' ) );

		$checks[] = $env->app_passwords_available()
			? $this->check( 'appPasswords', __( 'Application Passwords', 'mcp-builder' ), 'ok', __( 'Available', 'mcp-builder' ) )
			: $this->check( 'appPasswords', __( 'Application Passwords', 'mcp-builder' ), 'error', __( 'Unavailable', 'mcp-builder' ), null, __( 'They need HTTPS, or define( \'WP_ENVIRONMENT_TYPE\', \'local\' ) in wp-config.php on a local site. A security plugin may also be disabling them.', 'mcp-builder' ) );

		$checks[] = $env->pretty_permalinks()
			? $this->check( 'permalinks', __( 'Permalinks', 'mcp-builder' ), 'ok', __( 'Pretty', 'mcp-builder' ) )
			: $this->check( 'permalinks', __( 'Permalinks', 'mcp-builder' ), 'warning', __( 'Plain', 'mcp-builder' ), null, __( 'The endpoint will use the ?rest_route= form. Set Settings → Permalinks to "Post name" for a cleaner URL.', 'mcp-builder' ) );

		return $checks;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function version_check( string $id, string $label, string $current, string $required, string $fail_status ): array {
		$ok = version_compare( $current, $required, '>=' );
		return $this->check(
			$id,
			$label,
			$ok ? 'ok' : $fail_status,
			$current,
			$required,
			/* translators: 1: component name, 2: required version. */
			$ok ? '' : sprintf( __( 'Update %1$s to %2$s or newer.', 'mcp-builder' ), $label, $required )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function builder_check( string $id, string $label, ?string $current, string $required ): array {
		if ( null === $current ) {
			/* translators: %s: builder name. */
			return $this->check( $id, $label, 'inactive', null, $required, sprintf( __( '%s is not active, so its tools are hidden.', 'mcp-builder' ), $label ) );
		}
		return $this->version_check( $id, $label, $current, $required, 'warning' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function check( string $id, string $label, string $status, ?string $current = null, ?string $required = null, string $message = '' ): array {
		return array(
			'id'       => $id,
			'label'    => $label,
			'status'   => $status,
			'current'  => $current,
			'required' => $required,
			'message'  => $message,
		);
	}
}
