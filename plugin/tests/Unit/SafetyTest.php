<?php
/**
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Tests\Unit;

use Brain\Monkey\Functions;
use AmcpBuilder\Abilities\Registry;
use AmcpBuilder\Environment;
use AmcpBuilder\Safety\AuditLog;
use AmcpBuilder\Safety\Governance;
use AmcpBuilder\Safety\RateLimiter;
use AmcpBuilder\Safety\Snapshots;
use AmcpBuilder\Safety\Targets;
use AmcpBuilder\Settings;
use AmcpBuilder\Tests\TestCase;

final class SafetyTest extends TestCase {

	private function governance( array $stored = array(), bool $rate_ok = true ): Governance {
		Functions\when( 'get_option' )->alias( static fn( $name, $fallback = false ) => Settings::OPTION === $name ? $stored : $fallback );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_check_post_lock' )->justReturn( false );

		$settings = new Settings();
		$registry = new Registry( $settings, \Mockery::mock( Environment::class ), \Mockery::mock( Snapshots::class ) );
		$rate = \Mockery::mock( RateLimiter::class );
		$rate->shouldReceive( 'hit' )->andReturn( $rate_ok );

		return new Governance( $settings, $registry, \Mockery::mock( AuditLog::class ), \Mockery::mock( Snapshots::class ), $rate );
	}

	private function ability( string $name, bool $readonly ): \WP_Ability {
		return new \WP_Ability( $name, array( 'annotations' => array( 'readonly' => $readonly ) ) );
	}

	public function test_allows_by_default(): void {
		$this->assertNull( $this->governance()->check( 'amcp-builder/bricks-add-elements', array(), $this->ability( 'amcp-builder/bricks-add-elements', false ) ) );
	}

	public function test_kill_switch_blocks_everything(): void {
		$error = $this->governance( array( 'kill_switch' => true ) )->check( 'amcp-builder/site-info', null, $this->ability( 'amcp-builder/site-info', true ) );
		$this->assertSame( 'amcp_builder_disabled', $error->get_error_code() );
	}

	public function test_read_only_blocks_writes_but_not_reads(): void {
		$governance = $this->governance( array( 'read_only' => true ) );
		$this->assertSame( 'amcp_builder_read_only', $governance->check( 'bricks/set-page-elements', array(), $this->ability( 'bricks/set-page-elements', false ) )->get_error_code() );
		$this->assertNull( $governance->check( 'bricks/get-page-elements', array(), $this->ability( 'bricks/get-page-elements', true ) ) );
	}

	public function test_unknown_annotation_counts_as_write(): void {
		$error = $this->governance( array( 'read_only' => true ) )->check( 'elementor/x', array(), new \WP_Ability( 'elementor/x' ) );
		$this->assertSame( 'amcp_builder_read_only', $error->get_error_code() );
	}

	public function test_disabled_tool_is_blocked(): void {
		$error = $this->governance( array( 'disabled_tools' => array( 'amcp-builder/list-pages' ) ) )->check( 'amcp-builder/list-pages', null, $this->ability( 'amcp-builder/list-pages', true ) );
		$this->assertSame( 'amcp_builder_tool_disabled', $error->get_error_code() );
	}

	public function test_custom_code_is_off_by_default(): void {
		$error = $this->governance()->check( 'bricks/execute-php', array(), $this->ability( 'bricks/execute-php', false ) );
		$this->assertSame( 'amcp_builder_custom_code_disabled', $error->get_error_code() );
	}

	public function test_rate_limit(): void {
		$error = $this->governance( array(), false )->check( 'amcp-builder/list-pages', null, $this->ability( 'amcp-builder/list-pages', true ) );
		$this->assertSame( 'amcp_builder_rate_limited', $error->get_error_code() );
	}

	public function test_official_write_on_locked_post_is_blocked(): void {
		$governance = $this->governance();
		Functions\when( 'wp_check_post_lock' )->justReturn( 7 );
		Functions\when( 'get_userdata' )->justReturn( (object) array( 'display_name' => 'Amina' ) );

		$error = $governance->check( 'bricks/set-page-elements', array( 'postId' => 12 ), $this->ability( 'bricks/set-page-elements', false ) );
		$this->assertSame( 'amcp_builder_post_locked', $error->get_error_code() );
		$this->assertStringContainsString( 'Amina', $error->get_error_message() );
	}

	public function test_only_builder_namespaces_are_managed(): void {
		$this->assertTrue( Governance::is_managed( 'bricks/get-page-elements' ) );
		$this->assertTrue( Governance::is_managed( 'amcp-builder/site-info' ) );
		$this->assertFalse( Governance::is_managed( 'core/get-site-info' ) );
	}

	public function test_targets_resolve_post_ids_and_options(): void {
		$this->assertSame( 12, Targets::post_id( array( 'postId' => 12 ) ) );
		$this->assertSame( 9, Targets::post_id( (object) array( 'post_id' => '9' ) ) );
		$this->assertSame( 0, Targets::post_id( array( 'id' => 'abc123' ) ) );
		$this->assertSame( 'bricks_global_classes', Targets::option_for( 'bricks/set-global-classes' ) );
		$this->assertNull( Targets::option_for( 'elementor/manage-classes' ) );
	}

	public function test_audit_summary_redacts_secrets_and_truncates(): void {
		$summary = AuditLog::summarize(
			array(
				'postId'   => 3,
				'password' => 'hunter2',
				'nested'   => array( 'apiKey' => 'x' ),
				'text'     => str_repeat( 'a', 500 ),
			)
		);
		$this->assertStringNotContainsString( 'hunter2', $summary );
		$this->assertStringContainsString( '[redacted]', $summary );
		$this->assertLessThan( 400, strlen( $summary ) );
	}

	public function test_snapshot_payload_round_trip_rejects_objects(): void {
		$payload = array( 'meta' => array( '_bricks_page_content_2' => array( array( 'id' => 'abc123' ) ), '_elementor_data' => null ) );
		$this->assertSame( $payload, Snapshots::decode( Snapshots::encode( $payload ) ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$evil    = (string) gzcompress( serialize( array( 'x' => new \ArrayObject() ) ) );
		$decoded = Snapshots::decode( $evil );
		$this->assertInstanceOf( \__PHP_Incomplete_Class::class, $decoded['x'] );
	}
}
