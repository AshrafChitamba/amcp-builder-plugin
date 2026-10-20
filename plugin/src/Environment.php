<?php
/**
 * Detects the site's WordPress, PHP, MCP Adapter and builder setup.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only facts about the runtime. Builder checks are only reliable after
 * after_setup_theme (Bricks is a theme), so call them from init or later.
 */
class Environment {

	public function wp_version(): string {
		return (string) get_bloginfo( 'version' );
	}

	public function php_version(): string {
		return PHP_VERSION;
	}

	public function adapter_version(): ?string {
		if ( defined( 'WP_MCP_VERSION' ) ) {
			return (string) WP_MCP_VERSION;
		}
		return class_exists( '\WP\MCP\Core\McpAdapter' ) ? (string) \WP\MCP\Core\McpAdapter::VERSION : null;
	}

	public function has_abilities_api(): bool {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * Bricks version when Bricks is the active parent theme.
	 */
	public function bricks_version(): ?string {
		return ( 'bricks' === get_template() && defined( 'BRICKS_VERSION' ) ) ? (string) BRICKS_VERSION : null;
	}

	public function elementor_version(): ?string {
		return defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : null;
	}

	public function elementor_pro_version(): ?string {
		return defined( 'ELEMENTOR_PRO_VERSION' ) ? (string) ELEMENTOR_PRO_VERSION : null;
	}

	/**
	 * Whether Elementor's v4 Atomic editor is switched on.
	 */
	public function elementor_atomic_active(): bool {
		if ( null === $this->elementor_version() || ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		// VERIFY: experiment name in Elementor 4.3.x (modules/atomic-widgets/module.php EXPERIMENT_NAME).
		$experiments = \Elementor\Plugin::$instance->experiments ?? null;
		return $experiments && $experiments->is_feature_active( 'e_atomic_elements' );
	}

	public function is_https(): bool {
		return is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
	}

	public function app_passwords_available(): bool {
		return wp_is_application_passwords_available();
	}

	public function pretty_permalinks(): bool {
		return '' !== (string) get_option( 'permalink_structure' );
	}

	/**
	 * Summary used by the Status tab and the site-info tool.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'wordpress'        => $this->wp_version(),
			'php'              => $this->php_version(),
			'mcpAdapter'       => $this->adapter_version(),
			'bricks'           => $this->bricks_version(),
			'elementor'        => $this->elementor_version(),
			'elementorPro'     => $this->elementor_pro_version(),
			'elementorAtomic'  => $this->elementor_atomic_active(),
			'https'            => $this->is_https(),
			'appPasswords'     => $this->app_passwords_available(),
			'prettyPermalinks' => $this->pretty_permalinks(),
			'environmentType'  => wp_get_environment_type(),
		);
	}
}
