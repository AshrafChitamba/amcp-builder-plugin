<?php
/**
 * @package AcMcp
 */

namespace AcMcp\Tests\Unit;

use Brain\Monkey\Functions;
use AcMcp\Settings;
use AcMcp\Tests\TestCase;

final class SettingsTest extends TestCase {

	public function test_missing_option_returns_defaults(): void {
		Functions\when( 'get_option' )->justReturn( false );
		$this->assertSame( Settings::DEFAULTS, ( new Settings() )->all() );
	}

	public function test_sanitize_coerces_types_and_drops_unknown_keys(): void {
		$clean = ( new Settings() )->sanitize(
			array(
				'read_only'         => 'true',
				'rate_limit_writes' => '45',
				'snapshot_keep'     => 99999,
				'post_lock'         => 'EVERYTHING',
				'disabled_tools'    => array( 'ac-mcp/list-pages', 'Bad Name!', 'ac-mcp/list-pages', 42 ),
				'unknown'           => 'x',
			)
		);

		$this->assertTrue( $clean['read_only'] );
		$this->assertSame( 45, $clean['rate_limit_writes'] );
		$this->assertSame( 500, $clean['snapshot_keep'] );
		$this->assertSame( 'deny', $clean['post_lock'] );
		$this->assertSame( array( 'ac-mcp/list-pages', 'badname' ), $clean['disabled_tools'] );
		$this->assertArrayNotHasKey( 'unknown', $clean );
	}

	public function test_non_numeric_integer_falls_back_to_default(): void {
		$clean = ( new Settings() )->sanitize( array( 'audit_days' => 'forever' ) );
		$this->assertSame( Settings::DEFAULTS['audit_days'], $clean['audit_days'] );
	}

	public function test_update_merges_and_saves(): void {
		Functions\when( 'get_option' )->justReturn( array( 'read_only' => true ) );
		Functions\expect( 'update_option' )->once()->with( Settings::OPTION, \Mockery::on( static fn( $v ) => true === $v['read_only'] && true === $v['kill_switch'] ) );

		$saved = ( new Settings() )->update( array( 'kill_switch' => true ) );
		$this->assertTrue( $saved['kill_switch'] );
	}

	public function test_kill_switch_setting(): void {
		Functions\when( 'get_option' )->justReturn( array( 'kill_switch' => true ) );
		$this->assertTrue( ( new Settings() )->is_killed() );
	}
}
