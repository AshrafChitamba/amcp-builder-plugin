<?php
/**
 * Minimum-version checks.
 *
 * @package AcMcp
 */

namespace AcMcp;

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
		// No translated strings here: this runs on plugins_loaded, before the text domain may load.
		$adapter = $this->environment->adapter_version();

		return null !== $adapter
			&& version_compare( $this->environment->wp_version(), self::MIN_WP, '>=' )
			&& version_compare( $this->environment->php_version(), self::MIN_PHP, '>=' )
			&& version_compare( $adapter, self::MIN_ADAPTER, '>=' );
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
			$this->version_check( 'wordpress', __( 'WordPress', 'ac-mcp-for-bricks-elementor' ), $this->environment->wp_version(), self::MIN_WP, 'error' ),
			$this->version_check( 'php', __( 'PHP', 'ac-mcp-for-bricks-elementor' ), $this->environment->php_version(), self::MIN_PHP, 'error' ),
			null === $adapter
				? $this->check( 'mcpAdapter', __( 'MCP Adapter plugin', 'ac-mcp-for-bricks-elementor' ), 'error', null, self::MIN_ADAPTER, __( 'Install and activate the "MCP Adapter" plugin from Plugins → Add New.', 'ac-mcp-for-bricks-elementor' ) )
				: $this->version_check( 'mcpAdapter', __( 'MCP Adapter plugin', 'ac-mcp-for-bricks-elementor' ), $adapter, self::MIN_ADAPTER, 'error' ),
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

		$checks[] = $this->builder_check( 'bricks', __( 'Bricks', 'ac-mcp-for-bricks-elementor' ), $env->bricks_version(), self::MIN_BRICKS );
		$checks[] = $this->builder_check( 'elementor', __( 'Elementor', 'ac-mcp-for-bricks-elementor' ), $env->elementor_version(), self::MIN_ELEMENTOR );

		if ( $env->elementor_atomic_active() ) {
			$checks[] = $this->check( 'elementorAtomic', __( 'Elementor Atomic editor', 'ac-mcp-for-bricks-elementor' ), 'warning', __( 'On', 'ac-mcp-for-bricks-elementor' ), null, __( 'Pages built with Atomic (v4) elements can be read but not edited by AC MCP yet. Classic widgets and Containers are fully supported.', 'ac-mcp-for-bricks-elementor' ) );
		}

		$checks[] = $env->is_https()
			? $this->check( 'https', __( 'HTTPS', 'ac-mcp-for-bricks-elementor' ), 'ok', __( 'Yes', 'ac-mcp-for-bricks-elementor' ) )
			: $this->check( 'https', __( 'HTTPS', 'ac-mcp-for-bricks-elementor' ), 'local' === wp_get_environment_type() ? 'warning' : 'error', __( 'No', 'ac-mcp-for-bricks-elementor' ), null, __( 'Application Passwords are sent in every request. Use HTTPS on any site reachable from the internet.', 'ac-mcp-for-bricks-elementor' ) );

		$checks[] = $env->app_passwords_available()
			? $this->check( 'appPasswords', __( 'Application Passwords', 'ac-mcp-for-bricks-elementor' ), 'ok', __( 'Available', 'ac-mcp-for-bricks-elementor' ) )
			: $this->check( 'appPasswords', __( 'Application Passwords', 'ac-mcp-for-bricks-elementor' ), 'error', __( 'Unavailable', 'ac-mcp-for-bricks-elementor' ), null, __( 'They need HTTPS, or define( \'WP_ENVIRONMENT_TYPE\', \'local\' ) in wp-config.php on a local site. A security plugin may also be disabling them.', 'ac-mcp-for-bricks-elementor' ) );

		$checks[] = $env->pretty_permalinks()
			? $this->check( 'permalinks', __( 'Permalinks', 'ac-mcp-for-bricks-elementor' ), 'ok', __( 'Pretty', 'ac-mcp-for-bricks-elementor' ) )
			: $this->check( 'permalinks', __( 'Permalinks', 'ac-mcp-for-bricks-elementor' ), 'warning', __( 'Plain', 'ac-mcp-for-bricks-elementor' ), null, __( 'The endpoint will use the ?rest_route= form. Set Settings → Permalinks to "Post name" for a cleaner URL.', 'ac-mcp-for-bricks-elementor' ) );

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
			$ok ? '' : sprintf( __( 'Update %1$s to %2$s or newer.', 'ac-mcp-for-bricks-elementor' ), $label, $required )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function builder_check( string $id, string $label, ?string $current, string $required ): array {
		if ( null === $current ) {
			/* translators: %s: builder name. */
			return $this->check( $id, $label, 'inactive', null, $required, sprintf( __( '%s is not active, so its tools are hidden.', 'ac-mcp-for-bricks-elementor' ), $label ) );
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
