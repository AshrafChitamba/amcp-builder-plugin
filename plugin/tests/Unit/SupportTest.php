<?php
/**
 * @package McpBuilder
 */

namespace McpBuilder\Tests\Unit;

use McpBuilder\Support\Errors;
use McpBuilder\Support\Untrusted;
use McpBuilder\Tests\TestCase;

final class SupportTest extends TestCase {

	public function test_error_message_includes_fix_and_prefixed_code(): void {
		$error = Errors::make( 'post_not_found', 'Page 5 does not exist.', 'Call list-pages.', 404 );

		$this->assertSame( 'mcp_builder_post_not_found', $error->get_error_code() );
		$this->assertSame( 'Page 5 does not exist. How to fix: Call list-pages.', $error->get_error_message() );
		$this->assertSame( array( 'status' => 404 ), $error->get_error_data() );
	}

	public function test_error_without_fix_is_just_the_message(): void {
		$this->assertSame( 'Nope.', Errors::make( 'x', 'Nope.' )->get_error_message() );
	}

	public function test_untrusted_text_strips_markup_and_collapses_whitespace(): void {
		$this->assertSame( 'Hello & welcome', Untrusted::text( "<b>Hello</b>\n\n&amp; <script></script>welcome" ) );
	}

	public function test_untrusted_text_truncates(): void {
		$this->assertSame( 'abcd…', Untrusted::text( 'abcdefghij', 5 ) );
	}
}
