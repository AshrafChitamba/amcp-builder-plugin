<?php
/**
 * The site-info tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\General;

use McpBuilder\Abilities\Ability;
use McpBuilder\Requirements;
use McpBuilder\Support\Untrusted;

defined( 'ABSPATH' ) || exit;

/**
 * Tells the AI what this site supports before it starts working.
 */
final class SiteInfo extends Ability {

	public function slug(): string {
		return 'site-info';
	}

	public function label(): string {
		return __( 'Site info', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Call this first in every session. Returns which page builders are active (Bricks, Elementor) and their versions, '
			. 'whether the site is in read-only mode, whether writes default to dry run, and whether custom-code tools are enabled. '
			. 'Use it to decide which builder-specific tools to use. Only tools for active builders exist on this server.';
	}

	protected function properties(): array {
		return array();
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	protected function run( array $input ): array {
		$requirements = new Requirements( $this->environment );
		$env          = $this->environment;

		return array(
			'site'     => array(
				'name' => Untrusted::text( get_bloginfo( 'name' ) ),
				'url'  => home_url( '/' ),
			),
			'builders' => array(
				'bricks'    => array(
					'active'    => null !== $env->bricks_version(),
					'supported' => $requirements->bricks_supported(),
					'version'   => $env->bricks_version(),
				),
				'elementor' => array(
					'active'      => null !== $env->elementor_version(),
					'supported'   => $requirements->elementor_supported(),
					'version'     => $env->elementor_version(),
					'pro'         => $env->elementor_pro_version(),
					'atomicOn'    => $env->elementor_atomic_active(),
					'atomicNotes' => 'Atomic (v4) elements are read-only in MCP Builder. Classic widgets and Containers are fully editable.',
				),
			),
			'safety'   => array(
				'readOnly'          => (bool) $this->settings->get( 'read_only' ),
				'dryRunDefault'     => (bool) $this->settings->get( 'dry_run_default' ),
				'customCodeEnabled' => (bool) $this->settings->get( 'custom_code_enabled' ),
			),
			'versions' => array(
				'wordpress'  => $env->wp_version(),
				'mcpBuilder' => MCP_BUILDER_VERSION,
			),
			'notice'   => Untrusted::NOTICE,
		);
	}
}
