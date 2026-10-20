<?php

/**
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Tests\Unit;

use AmcpBuilder\Migration;
use AmcpBuilder\Tests\TestCase;

final class MigrationTest extends TestCase
{

	public function test_renames_own_tools_and_keeps_official_ones(): void
	{
		$this->assertSame('amcp-builder/site-info', Migration::rename_tool('mcp-builder/site-info'));
		$this->assertSame('amcp-builder/bricks-add-elements', Migration::rename_tool('mcp-builder/bricks-add-elements'));
		$this->assertSame('bricks/set-page-elements', Migration::rename_tool('bricks/set-page-elements'));
		$this->assertSame('amcp-builder/site-info', Migration::rename_tool('amcp-builder/site-info'));
	}
}
