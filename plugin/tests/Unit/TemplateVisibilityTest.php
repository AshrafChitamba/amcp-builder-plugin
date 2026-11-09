<?php
/**
 * @package AcMcp
 */

namespace AcMcp\Tests\Unit;

use AcMcp\Abilities\Bricks\ListTemplates as ListBricksTemplates;
use AcMcp\Abilities\Elementor\ListTemplates as ListElementorTemplates;
use AcMcp\Environment;
use AcMcp\Safety\Snapshots;
use AcMcp\Settings;
use AcMcp\Tests\TestCase;
use Brain\Monkey\Functions;
use ReflectionMethod;

/** Checks that template results respect per-post WordPress read capabilities. */
final class TemplateVisibilityTest extends TestCase {

	protected function tearDown(): void {
		\WP_Query::$test_posts = array();
		parent::tearDown();
	}

	private function posts(): array {
		$readable              = new \WP_Post();
		$readable->ID          = 21;
		$readable->post_title  = 'Readable draft';
		$readable->post_status = 'draft';
		$readable->post_type   = 'wp_template';

		$private              = new \WP_Post();
		$private->ID          = 22;
		$private->post_title  = 'Someone else private template';
		$private->post_status = 'private';
		$private->post_type   = 'wp_template';

		return array( $readable, $private );
	}

	private function settings(): Settings {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'current_user_can' )->alias( static fn( $capability, $post_id = null ): bool => 'read_post' === $capability && 21 === $post_id );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_permalink' )->alias( static fn( $post ): string => 'https://example.test/?p=' . $post->ID );
		Functions\when( 'add_query_arg' )->justReturn( 'https://example.test/editor' );
		Functions\when( 'admin_url' )->justReturn( 'https://example.test/wp-admin/post.php' );
		Functions\when( 'wp_is_block_theme' )->justReturn( false );
		return new Settings();
	}

	private function run_list( object $ability ): array {
		$method = new ReflectionMethod( $ability, 'run' );
		return $method->invoke( $ability, array( 'type' => 'any' ) );
	}

	public function test_bricks_template_listing_checks_read_permission_per_template(): void {
		\WP_Query::$test_posts = $this->posts();
		$ability               = new ListBricksTemplates( $this->settings(), \Mockery::mock( Environment::class ), \Mockery::mock( Snapshots::class ) );

		$result = $this->run_list( $ability );

		$this->assertSame( array( 21 ), array_column( $result['templates'], 'id' ) );
	}

	public function test_elementor_template_listing_checks_read_permission_per_template(): void {
		\WP_Query::$test_posts = $this->posts();
		$ability               = new ListElementorTemplates( $this->settings(), \Mockery::mock( Environment::class ), \Mockery::mock( Snapshots::class ) );

		$result = $this->run_list( $ability );

		$this->assertSame( array( 21 ), array_column( $result['templates'], 'id' ) );
	}
}
