<?php
/**
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Tests\Unit;

use AmcpBuilder\Elementor\Validator;
use AmcpBuilder\Support\TreeError;
use AmcpBuilder\Tests\ElementorArrayCatalog;
use AmcpBuilder\Tests\TestCase;

final class ElementorValidatorTest extends TestCase {

	private function validator(): Validator {
		return new Validator( new ElementorArrayCatalog() );
	}

	private function assertRefused( string $code, callable $call ): TreeError {
		try {
			$call();
		} catch ( TreeError $error ) {
			$this->assertSame( $code, $error->error_code, $error->getMessage() );
			return $error;
		}
		$this->fail( "Expected TreeError {$code}." );
	}

	public function test_accepts_a_container_section_with_widgets(): void {
		$this->validator()->specs(
			array(
				array(
					'elType'   => 'container',
					'settings' => array( 'padding_mobile' => array( 'unit' => 'px', 'top' => '24' ) ),
					'elements' => array(
						array( 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Hi', '__globals__' => array( 'title_color' => 'globals/colors?id=primary' ) ) ),
						array( 'elType' => 'container', 'elements' => array( array( 'elType' => 'widget', 'widgetType' => 'image' ) ) ),
					),
				),
			),
			null
		);
		$this->addToAssertionCount( 1 );
	}

	public function test_widget_at_top_level_is_refused_with_wrap_hint(): void {
		$error = $this->assertRefused( 'invalid_nesting', fn() => $this->validator()->specs( array( array( 'elType' => 'widget', 'widgetType' => 'heading' ) ), null ) );
		$this->assertStringContainsString( 'container', $error->fix );
	}

	public function test_unknown_widget_and_missing_widget_type(): void {
		$this->assertRefused( 'unknown_widget', fn() => $this->validator()->specs( array( array( 'elType' => 'widget', 'widgetType' => 'carousel-3000' ) ), array( 'elType' => 'container' ) ) );
		$this->assertRefused( 'invalid_element', fn() => $this->validator()->specs( array( array( 'elType' => 'widget' ) ), array( 'elType' => 'container' ) ) );
		$this->assertRefused( 'invalid_element', fn() => $this->validator()->specs( array( array( 'settings' => array() ) ), null ) );
	}

	public function test_unknown_setting_suggests_close_keys(): void {
		$error = $this->assertRefused(
			'unknown_setting',
			fn() => $this->validator()->specs( array( array( 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'titel' => 'x' ) ) ), array( 'elType' => 'container' ) )
		);
		$this->assertStringContainsString( 'Close matches: title', $error->getMessage() );
		$this->assertStringContainsString( 'elementor-get-widget-schema', $error->fix );
	}

	public function test_global_references_are_checked(): void {
		$this->assertRefused( 'invalid_globals', fn() => $this->validator()->settings( 'widget', 'heading', array( '__globals__' => array( 'title_color' => '#fff' ) ) ) );
		$this->assertRefused( 'invalid_globals', fn() => $this->validator()->settings( 'widget', 'heading', array( '__globals__' => array( 'globals/colors?id=x' ) ) ) );
		$this->assertRefused( 'unknown_setting', fn() => $this->validator()->settings( 'widget', 'heading', array( '__globals__' => array( 'nope_color' => 'globals/colors?id=primary' ) ) ) );
	}

	public function test_widgets_hold_children_only_when_nestable(): void {
		$this->assertRefused(
			'not_nestable',
			fn() => $this->validator()->specs( array( array( 'elType' => 'container' ) ), array( 'elType' => 'widget', 'widgetType' => 'heading' ) )
		);
		$this->validator()->specs( array( array( 'elType' => 'container' ) ), array( 'elType' => 'widget', 'widgetType' => 'nested-tabs' ) );
		$this->addToAssertionCount( 1 );
	}

	public function test_sections_only_hold_columns(): void {
		$this->assertRefused( 'invalid_nesting', fn() => $this->validator()->specs( array( array( 'elType' => 'widget', 'widgetType' => 'heading' ) ), array( 'elType' => 'section' ) ) );
		$this->validator()->specs( array( array( 'elType' => 'widget', 'widgetType' => 'heading' ) ), array( 'elType' => 'column' ) );
		$this->addToAssertionCount( 1 );
	}

	public function test_atomic_elements_are_refused(): void {
		$this->assertRefused( 'atomic_not_supported', fn() => $this->validator()->specs( array( array( 'elType' => 'e-flexbox' ) ), null ) );
		$this->assertRefused( 'atomic_not_supported', fn() => $this->validator()->specs( array( array( 'elType' => 'container' ) ), array( 'elType' => 'e-div-block' ) ) );
		$this->assertRefused( 'atomic_not_supported', fn() => $this->validator()->editable( array( 'elType' => 'widget', 'widgetType' => 'e-heading' ) ) );
	}

	public function test_too_many_elements_in_one_call(): void {
		$widgets = array_fill( 0, Validator::MAX_ELEMENTS, array( 'elType' => 'widget', 'widgetType' => 'heading' ) );
		$this->assertRefused( 'too_many_elements', fn() => $this->validator()->specs( array( array( 'elType' => 'container', 'elements' => $widgets ) ), null ) );
	}

	public function test_known_keys_helper_for_page_settings(): void {
		$error = $this->assertRefused( 'unknown_setting', fn() => Validator::known_keys( array( 'templat' => 'x' ), array( 'template', 'hide_title' ), 'this page', 'Fix.' ) );
		$this->assertStringContainsString( 'Close matches: template', $error->getMessage() );
	}

	public function test_html_widget_needs_custom_code_permission(): void {
		$html = array( array( 'elType' => 'container', 'elements' => array( array( 'elType' => 'widget', 'widgetType' => 'html', 'settings' => array( 'html' => '<p>x</p>' ) ) ) ) );
		try {
			$this->validator()->specs( $html, null );
			$this->fail( 'HTML widget accepted without permission.' );
		} catch ( \AmcpBuilder\Support\TreeError $error ) {
			$this->assertSame( 'custom_code_disabled', $error->error_code );
		}
		( new Validator( new ElementorArrayCatalog(), true ) )->specs( $html, null );
		$this->addToAssertionCount( 1 );
	}
}
