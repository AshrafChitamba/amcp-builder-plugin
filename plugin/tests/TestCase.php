<?php
/**
 * Base test case with Brain Monkey.
 *
 * @package AcMcp
 */

namespace AcMcp\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\stubs(
			array(
				'is_wp_error'           => static fn( $thing ): bool => $thing instanceof \WP_Error,
				'rest_sanitize_boolean' => static fn( $value ): bool => is_string( $value ) ? ! in_array( strtolower( $value ), array( '', 'false', '0' ), true ) : (bool) $value,
				'sanitize_key'          => static fn( $key ): string => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ),
				'sanitize_text_field'   => static fn( $text ): string => trim( strip_tags( (string) $text ) ),
				'sanitize_textarea_field' => static fn( $text ): string => trim( strip_tags( (string) $text ) ),
				'wp_strip_all_tags'     => static fn( $text ): string => trim( strip_tags( (string) $text ) ),
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
