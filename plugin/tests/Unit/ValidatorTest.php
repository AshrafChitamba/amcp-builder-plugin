<?php
/**
 * @package AcMcp
 */

namespace AcMcp\Tests\Unit;

use AcMcp\Support\TreeError;
use AcMcp\Bricks\Validator;
use AcMcp\Tests\ArrayCatalog;
use AcMcp\Tests\TestCase;

final class ValidatorTest extends TestCase {

	private function validator(): Validator {
		return new Validator( ArrayCatalog::default() );
	}

	private function assertRejects( string $code, callable $call ): TreeError {
		try {
			$call();
		} catch ( TreeError $error ) {
			$this->assertSame( $code, $error->error_code );
			return $error;
		}
		$this->fail( "Expected TreeError {$code}." );
	}

	public function test_accepts_valid_nested_specs_with_responsive_keys(): void {
		$this->validator()->specs(
			array(
				array(
					'name'     => 'section',
					'children' => array(
						array(
							'name'     => 'container',
							'settings' => array(
								'_display'                             => 'grid',
								'_gridTemplateColumns'                 => '1fr 1fr',
								'_gridTemplateColumns:mobile_portrait' => '1fr',
								'_cssGlobalClasses'                    => array( 'abc123' ),
							),
							'children' => array( array( 'name' => 'heading', 'settings' => array( 'text' => 'Hi' ) ) ),
						),
					),
				),
			),
			null
		);
		$this->addToAssertionCount( 1 );
	}

	public function test_rejects_unknown_element(): void {
		$this->assertRejects( 'unknown_element', fn() => $this->validator()->specs( array( array( 'name' => 'carousel-3d' ) ), null ) );
	}

	public function test_rejects_unknown_setting_with_suggestions(): void {
		$error = $this->assertRejects( 'unknown_setting', fn() => $this->validator()->specs( array( array( 'name' => 'heading', 'settings' => array( 'txt' => 'Hi' ) ) ), null ) );
		$this->assertStringContainsString( 'text', $error->getMessage() );
		$this->assertStringContainsString( 'bricks-get-element-schema', $error->fix );
	}

	public function test_rejects_children_on_non_nestable_element(): void {
		$this->assertRejects(
			'not_nestable',
			fn() => $this->validator()->specs( array( array( 'name' => 'heading', 'children' => array( array( 'name' => 'heading' ) ) ) ), null )
		);
	}

	public function test_rejects_non_nestable_parent(): void {
		$this->assertRejects( 'not_nestable', fn() => $this->validator()->specs( array( array( 'name' => 'heading' ) ), 'image' ) );
	}

	public function test_rejects_list_as_settings(): void {
		$this->assertRejects( 'invalid_settings', fn() => $this->validator()->settings( 'heading', array( 'a', 'b' ) ) );
	}

	public function test_rejects_empty_or_non_list_elements(): void {
		$this->assertRejects( 'invalid_elements', fn() => $this->validator()->specs( array(), null ) );
		$this->assertRejects( 'invalid_elements', fn() => $this->validator()->specs( array( 'name' => 'heading' ), null ) );
	}

	public function test_rejects_too_many_elements(): void {
		$many = array_fill( 0, Validator::MAX_ELEMENTS + 1, array( 'name' => 'heading' ) );
		$this->assertRejects( 'too_many_elements', fn() => $this->validator()->specs( $many, null ) );
	}

	public function test_code_element_needs_custom_code_permission(): void {
		$code = array( array( 'name' => 'code', 'settings' => array( 'code' => '<p>x</p>' ) ) );
		$this->assertRejects( 'custom_code_disabled', fn() => $this->validator()->specs( $code, null ) );
		( new Validator( ArrayCatalog::default(), true ) )->specs( $code, null );
		$this->addToAssertionCount( 1 );
	}
}
