<?php
/**
 * @package McpBuilder
 */

namespace McpBuilder\Tests\Unit;

use Brain\Monkey\Functions;
use McpBuilder\Abilities\Elementor\UpdateGlobals;
use McpBuilder\Abilities\General\CreatePage;
use McpBuilder\Abilities\General\ListPages;
use McpBuilder\Abilities\General\SiteInfo;
use McpBuilder\Abilities\Registry;
use McpBuilder\Elementor\Templates;
use McpBuilder\Environment;
use McpBuilder\Safety\Snapshots;
use McpBuilder\Settings;
use McpBuilder\Tests\TestCase;

final class AbilitiesTest extends TestCase {

	private function environment( ?string $bricks = '2.4.2', ?string $elementor = null ): Environment {
		$environment = \Mockery::mock( Environment::class )->makePartial();
		$environment->shouldReceive( 'bricks_version' )->andReturn( $bricks );
		$environment->shouldReceive( 'elementor_version' )->andReturn( $elementor );
		return $environment;
	}

	private function snapshots(): Snapshots {
		return \Mockery::mock( Snapshots::class );
	}

	private function settings( array $stored = array() ): Settings {
		Functions\when( 'get_option' )->alias(
			static fn( $name, $fallback = false ) => match ( $name ) {
				Settings::OPTION         => $stored,
				'bricks_global_settings' => array( 'postTypes' => array( 'page' ) ),
				default                  => $fallback,
			}
		);
		return new Settings();
	}

	public function test_registry_names_are_namespaced(): void {
		$registry = new Registry( $this->settings(), $this->environment(), $this->snapshots() );
		$this->assertSame(
			array(
				'mcp-builder/site-info',
				'mcp-builder/get-design-guide',
				'mcp-builder/list-pages',
				'mcp-builder/create-page',
				'mcp-builder/list-media',
				'mcp-builder/upload-media',
				'mcp-builder/update-page',
				'mcp-builder/list-snapshots',
				'mcp-builder/restore-snapshot',
			),
			$registry->names(),
			'Bricks tools stay hidden when Bricks classes are not loaded.'
		);
	}

	public function test_ability_args_are_mcp_only_with_annotations(): void {
		$args = ( new ListPages( $this->settings(), $this->environment(), $this->snapshots() ) )->to_args();

		$this->assertSame( Registry::CATEGORY, $args['category'] );
		$this->assertTrue( $args['meta']['annotations']['readonly'] );
		$this->assertFalse( $args['meta']['annotations']['destructive'] );
		$this->assertFalse( $args['meta']['mcp']['public'] );
		$this->assertFalse( $args['meta']['show_in_rest'] );
		$this->assertSame( 'object', $args['input_schema']['type'] );
		$this->assertFalse( $args['input_schema']['additionalProperties'] );
		$this->assertSame( 50, $args['input_schema']['properties']['perPage']['maximum'] );
	}

	public function test_required_keys_are_declared(): void {
		$schema = ( new CreatePage( $this->settings(), $this->environment(), $this->snapshots() ) )->input_schema();
		$this->assertSame( array( 'title', 'builder' ), $schema['required'] );
	}

	public function test_site_info_reports_builders_and_safety(): void {
		Functions\when( 'get_bloginfo' )->alias( static fn( $key ) => 'version' === $key ? '7.1.3' : '<b>My Site</b>' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );

		$environment = $this->environment();
		$environment->shouldReceive( 'elementor_atomic_active' )->andReturn( false );
		$environment->shouldReceive( 'elementor_pro_version' )->andReturn( null );

		$result = ( new SiteInfo( $this->settings( array( 'read_only' => true ) ), $environment, $this->snapshots() ) )->execute( null );

		$this->assertSame( 'My Site', $result['site']['name'] );
		$this->assertTrue( $result['builders']['bricks']['active'] );
		$this->assertTrue( $result['builders']['bricks']['supported'] );
		$this->assertFalse( $result['builders']['elementor']['active'] );
		$this->assertTrue( $result['safety']['readOnly'] );
		$this->assertArrayHasKey( 'notice', $result );
	}

	public function test_create_page_dry_run_saves_nothing(): void {
		Functions\when( 'get_post_type_object' )->justReturn( new \WP_Post_Type( 'page' ) );
		Functions\expect( 'wp_insert_post' )->never();

		$result = ( new CreatePage( $this->settings(), $this->environment(), $this->snapshots() ) )->execute(
			array(
				'title'   => 'About us',
				'builder' => 'bricks',
				'dryRun'  => true,
			)
		);

		$this->assertTrue( $result['dryRun'] );
		$this->assertSame( 'About us', $result['wouldCreate']['post_title'] );
		$this->assertSame( 'draft', $result['wouldCreate']['post_status'] );
	}

	public function test_create_page_dry_run_follows_site_default(): void {
		Functions\when( 'get_post_type_object' )->justReturn( new \WP_Post_Type( 'page' ) );
		Functions\expect( 'wp_insert_post' )->never();

		$result = ( new CreatePage( $this->settings( array( 'dry_run_default' => true ) ), $this->environment(), $this->snapshots() ) )->execute(
			array(
				'title'   => 'About us',
				'builder' => 'bricks',
			)
		);
		$this->assertTrue( $result['dryRun'] );
	}

	public function test_create_page_rejects_inactive_builder(): void {
		Functions\when( 'get_post_type_object' )->justReturn( new \WP_Post_Type( 'page' ) );

		$result = ( new CreatePage( $this->settings(), $this->environment(), $this->snapshots() ) )->execute(
			array(
				'title'   => 'About us',
				'builder' => 'elementor',
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'mcp_builder_builder_inactive', $result->get_error_code() );
		$this->assertStringContainsString( 'How to fix', $result->get_error_message() );
	}

	public function test_create_page_rejects_post_type_not_enabled_for_bricks(): void {
		Functions\when( 'get_post_type_object' )->justReturn( new \WP_Post_Type( 'post' ) );

		$result = ( new CreatePage( $this->settings(), $this->environment(), $this->snapshots() ) )->execute(
			array(
				'title'    => 'News',
				'builder'  => 'bricks',
				'postType' => 'post',
			)
		);

		$this->assertSame( 'mcp_builder_post_type_not_enabled', $result->get_error_code() );
	}

	public function test_create_page_publish_requires_capability(): void {
		Functions\when( 'get_post_type_object' )->justReturn( new \WP_Post_Type( 'page' ) );
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = ( new CreatePage( $this->settings(), $this->environment(), $this->snapshots() ) )->execute(
			array(
				'title'   => 'About us',
				'builder' => 'bricks',
				'status'  => 'publish',
			)
		);

		$this->assertSame( 'mcp_builder_cannot_publish', $result->get_error_code() );
	}

	public function test_elementor_global_fonts_reject_unknown_fields(): void {
		$schema = ( new UpdateGlobals( $this->settings(), $this->environment(), $this->snapshots() ) )->input_schema();
		$font   = $schema['properties']['typography']['items'];

		$this->assertFalse( $font['additionalProperties'], 'A wrong shape such as {"settings":{...}} must be rejected, not silently ignored.' );
		$this->assertArrayHasKey( 'font_family', $font['properties'] );
		$this->assertSame( array( 'size' ), $font['properties']['font_size']['required'] );
	}

	public function test_block_themes_warn_about_elementor_headers_and_footers(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );
		$this->assertCount( 1, Templates::theme_warnings( 'header' ) );
		$this->assertCount( 1, Templates::theme_warnings( 'footer' ) );
		$this->assertSame( array(), Templates::theme_warnings( 'single-page' ) );

		Functions\when( 'wp_is_block_theme' )->justReturn( false );
		$this->assertSame( array(), Templates::theme_warnings( 'header' ) );
	}
}
