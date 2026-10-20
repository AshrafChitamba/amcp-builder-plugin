<?php
/**
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Tests\Unit;

use Brain\Monkey\Functions;
use AmcpBuilder\Environment;
use AmcpBuilder\Requirements;
use AmcpBuilder\Tests\TestCase;

final class RequirementsTest extends TestCase {

	private function requirements( string $wp, ?string $adapter ): Requirements {
		Functions\when( 'get_bloginfo' )->justReturn( $wp );
		$environment = \Mockery::mock( Environment::class )->makePartial();
		$environment->shouldReceive( 'adapter_version' )->andReturn( $adapter );
		return new Requirements( $environment );
	}

	public function test_runs_when_core_requirements_met(): void {
		$this->assertTrue( $this->requirements( '7.1.3', '0.7.0' )->can_run() );
	}

	public function test_blocked_without_mcp_adapter(): void {
		$requirements = $this->requirements( '7.1.3', null );
		$this->assertFalse( $requirements->can_run() );

		$adapter = array_values( array_filter( $requirements->core_checks(), static fn( $c ) => 'mcpAdapter' === $c['id'] ) )[0];
		$this->assertSame( 'error', $adapter['status'] );
		$this->assertStringContainsString( 'MCP Adapter', $adapter['message'] );
	}

	public function test_blocked_on_old_wordpress(): void {
		$this->assertFalse( $this->requirements( '6.9', '0.7.0' )->can_run() );
	}

	public function test_blocked_on_old_adapter(): void {
		$this->assertFalse( $this->requirements( '7.1', '0.6.1' )->can_run() );
	}
}
