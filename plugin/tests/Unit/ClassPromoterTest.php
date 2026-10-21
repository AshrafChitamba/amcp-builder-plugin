<?php
/**
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Tests\Unit;

use AmcpBuilder\Bricks\ClassPromoter;
use AmcpBuilder\Tests\TestCase;

final class ClassPromoterTest extends TestCase {

	private function promoter(): ClassPromoter {
		return new ClassPromoter(
			array(
				array( 'id' => 'abc123', 'name' => 'card', 'settings' => array() ),
			)
		);
	}

	public function test_plain_classes_become_global_classes(): void {
		$promoter = $this->promoter();
		$settings = $promoter->settings( array( 'text' => 'Hi', '_cssClasses' => ' card  hero-title ' ) );

		$this->assertArrayNotHasKey( '_cssClasses', $settings );
		$this->assertSame( 'abc123', $settings['_cssGlobalClasses'][0], 'Reuses the existing class by name.' );
		$this->assertCount( 2, $settings['_cssGlobalClasses'] );

		$pending = $promoter->pending();
		$this->assertCount( 1, $pending, 'Only the unknown class is created.' );
		$this->assertSame( 'hero-title', $pending[0]['name'] );
		$this->assertSame( $settings['_cssGlobalClasses'][1], $pending[0]['id'] );
		$this->assertMatchesRegularExpression( '/^[a-z][a-z0-9]{5}$/', $pending[0]['id'] );
	}

	public function test_keeps_existing_global_classes_and_creates_each_name_once(): void {
		$promoter = $this->promoter();
		$specs    = $promoter->specs(
			array(
				array(
					'name'     => 'block',
					'settings' => array( '_cssGlobalClasses' => array( 'zzz999' ), '_cssClasses' => 'badge' ),
					'children' => array(
						array( 'name' => 'icon', 'settings' => array( '_cssClasses' => 'badge' ) ),
					),
				),
			)
		);

		$parent = $specs[0]['settings']['_cssGlobalClasses'];
		$child  = $specs[0]['children'][0]['settings']['_cssGlobalClasses'];
		$this->assertSame( 'zzz999', $parent[0] );
		$this->assertSame( $parent[1], $child[0], 'The same name maps to the same new class.' );
		$this->assertCount( 1, $promoter->pending() );
	}

	public function test_leaves_settings_without_plain_classes_alone(): void {
		$promoter = $this->promoter();
		$this->assertSame( array( 'text' => 'Hi' ), $promoter->settings( array( 'text' => 'Hi' ) ) );
		$this->assertSame( array( '_cssClasses' => null ), $promoter->settings( array( '_cssClasses' => null ) ), 'A null removal in merge mode passes through.' );
		$this->assertSame( array(), $promoter->settings( array( '_cssClasses' => '   ' ) ) );
		$this->assertSame( array(), $promoter->pending() );
	}
}
