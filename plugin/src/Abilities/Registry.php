<?php
/**
 * Registers MCP Builder abilities with the WordPress Abilities API.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities;

use McpBuilder\Environment;
use McpBuilder\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the list of tools and registers the available ones.
 */
final class Registry {

	public const CATEGORY = 'mcp-builder';

	/** @var list<Ability>|null */
	private ?array $abilities = null;

	public function __construct(
		private Settings $settings,
		private Environment $environment
	) {}

	public function register(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'MCP Builder', 'mcp-builder' ),
				'description' => __( 'Build and edit Bricks and Elementor pages.', 'mcp-builder' ),
			)
		);
	}

	public function register_abilities(): void {
		foreach ( $this->available() as $ability ) {
			wp_register_ability( $ability->name(), $ability->to_args() );
		}
	}

	/**
	 * Every ability the plugin ships, available or not.
	 *
	 * @return list<Ability>
	 */
	public function all(): array {
		return $this->abilities ??= array_map(
			fn( string $class_name ): Ability => new $class_name( $this->settings, $this->environment ),
			array(
				General\SiteInfo::class,
				General\ListPages::class,
				General\CreatePage::class,
			)
		);
	}

	/**
	 * Abilities usable on this site (their builder is active).
	 *
	 * @return list<Ability>
	 */
	public function available(): array {
		return array_values( array_filter( $this->all(), static fn( Ability $ability ): bool => $ability->is_available() ) );
	}

	/**
	 * Ability names usable on this site.
	 *
	 * @return list<string>
	 */
	public function names(): array {
		return array_map( static fn( Ability $ability ): string => $ability->name(), $this->available() );
	}
}
