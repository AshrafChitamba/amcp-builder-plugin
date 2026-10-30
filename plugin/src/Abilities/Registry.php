<?php
/**
 * Registers AC MCP for Bricks and Elementor abilities with the WordPress Abilities API.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities;

use AcMcp\Environment;
use AcMcp\Safety\Snapshots;
use AcMcp\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the list of tools and registers the available ones.
 */
final class Registry {

	public const CATEGORY = 'ac-mcp';

	/** @var list<Ability>|null */
	private ?array $abilities = null;

	public function __construct(
		private Settings $settings,
		private Environment $environment,
		private Snapshots $snapshots
	) {}

	public function register(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'AC MCP for Bricks and Elementor', 'ac-mcp-for-bricks-elementor' ),
				'description' => __( 'Build and edit Bricks and Elementor pages.', 'ac-mcp-for-bricks-elementor' ),
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
			fn( string $class_name ): Ability => new $class_name( $this->settings, $this->environment, $this->snapshots ),
			array(
				General\SiteInfo::class,
				Guides\GetDesignGuide::class,
				Guides\DesignGuideResource::class,
				Guides\BricksGuideResource::class,
				Guides\ElementorGuideResource::class,
				Guides\SafetyGuideResource::class,
				Guides\DesignPagePrompt::class,
				Guides\ReviewPagePrompt::class,
				General\ListPages::class,
				General\CreatePage::class,
				General\ListMedia::class,
				General\UploadMedia::class,
				General\UpdatePage::class,
				General\ListSnapshots::class,
				General\RestoreSnapshot::class,
				Bricks\GetPage::class,
				Bricks\ListElements::class,
				Bricks\GetElementSchema::class,
				Bricks\AddElements::class,
				Bricks\UpdateElement::class,
				Bricks\MoveElement::class,
				Bricks\DuplicateElement::class,
				Bricks\RemoveElement::class,
				Bricks\ListGlobalClasses::class,
				Bricks\UpsertGlobalClass::class,
				Bricks\ApplyGlobalClass::class,
				Bricks\ListTemplates::class,
				Bricks\CreateTemplate::class,
				Bricks\SetTemplateConditions::class,
				Bricks\GetDesignTokens::class,
				Bricks\UpsertColors::class,
				Bricks\UpsertVariables::class,
				Bricks\UpdateThemeStyle::class,
				Bricks\UpdatePageSettings::class,
				Bricks\CodeStatus::class,
				Elementor\GetPage::class,
				Elementor\ListWidgets::class,
				Elementor\GetWidgetSchema::class,
				Elementor\GetGlobals::class,
				Elementor\AddElements::class,
				Elementor\UpdateElement::class,
				Elementor\MoveElement::class,
				Elementor\DuplicateElement::class,
				Elementor\RemoveElement::class,
				Elementor\UpdatePageSettings::class,
				Elementor\ListTemplates::class,
				Elementor\CreateTemplate::class,
				Elementor\SetConditions::class,
				Elementor\UpdateGlobals::class,
				Elementor\UpsertCodeSnippet::class,
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
	 * One of the plugin's abilities by full name, available or not.
	 */
	public function find( string $name ): ?Ability {
		foreach ( $this->all() as $ability ) {
			if ( $ability->name() === $name ) {
				return $ability;
			}
		}
		return null;
	}

	/**
	 * Ability names usable on this site.
	 *
	 * @return list<string>
	 */
	public function names( string $type = 'tool' ): array {
		return array_values(
			array_map(
				static fn( Ability $ability ): string => $ability->name(),
				array_filter( $this->available(), static fn( Ability $ability ): bool => $ability->mcp_type() === $type )
			)
		);
	}
}
