<?php

/**
 * @package AcMcp
 */

namespace AcMcp\Tests\Unit;

use AcMcp\Migration;
use AcMcp\Tests\TestCase;

final class MigrationTest extends TestCase
{

	public function test_renames_own_tools_and_keeps_official_ones(): void
	{
		$this->assertSame('ac-mcp/site-info', Migration::rename_tool('mcp-builder/site-info'));
		$this->assertSame('ac-mcp/bricks-add-elements', Migration::rename_tool('mcp-builder/bricks-add-elements'));
		$this->assertSame('bricks/set-page-elements', Migration::rename_tool('bricks/set-page-elements'));
		$this->assertSame('ac-mcp/site-info', Migration::rename_tool('ac-mcp/site-info'));
	}
}
