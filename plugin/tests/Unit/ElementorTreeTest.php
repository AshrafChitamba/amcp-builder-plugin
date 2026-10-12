<?php
/**
 * @package McpBuilder
 */

namespace McpBuilder\Tests\Unit;

use McpBuilder\Elementor\Ids;
use McpBuilder\Elementor\Tree;
use McpBuilder\Support\Diff;
use McpBuilder\Support\TreeError;
use McpBuilder\Tests\TestCase;

final class ElementorTreeTest extends TestCase {

	/**
	 * container(c1) > [container(c2) > heading(h1), text(t1)]; container(c3).
	 */
	private function page(): Tree {
		return new Tree(
			array(
				array(
					'id'       => 'c1',
					'elType'   => 'container',
					'settings' => array( 'content_width' => 'boxed' ),
					'isInner'  => false,
					'elements' => array(
						array(
							'id'       => 'c2',
							'elType'   => 'container',
							'settings' => array(),
							'isInner'  => true,
							'elements' => array(
								array( 'id' => 'h1', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Hi' ), 'elements' => array(), 'isInner' => false ),
							),
						),
						array( 'id' => 't1', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => array( 'editor' => '<p>Body</p>' ), 'elements' => array(), 'isInner' => false ),
					),
				),
				array( 'id' => 'c3', 'elType' => 'container', 'settings' => array(), 'elements' => array(), 'isInner' => false ),
			)
		);
	}

	public function test_reads_children_parents_and_descendants(): void {
		$tree = $this->page();
		$this->assertSame( array( 'c1', 'c3' ), $tree->children( null ) );
		$this->assertSame( array( 'c2', 't1' ), $tree->children( 'c1' ) );
		$this->assertSame( array( 'c2', 'h1', 't1' ), $tree->descendants( 'c1' ) );
		$this->assertSame( 'c2', $tree->parent_id( 'h1' ) );
		$this->assertNull( $tree->parent_id( 'c3' ) );
		$this->assertSame( 5, $tree->count() );
		$this->assertSame( 'containers', $tree->structure() );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_insert_builds_nested_elements_with_inner_flags(): void {
		$tree    = $this->page();
		$created = $tree->insert(
			array(
				array(
					'elType'   => 'container',
					'elements' => array(
						array( 'elType' => 'container', 'elements' => array( array( 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'New' ) ) ) ),
					),
				),
			),
			null,
			1,
			new Ids( $tree->ids() )
		);

		$this->assertCount( 3, $created );
		$this->assertSame( array( 'c1', $created[0], 'c3' ), $tree->children( null ) );

		$outer = $tree->get( $created[0] );
		$inner = $tree->get( $created[1] );
		$head  = $tree->get( $created[2] );
		$this->assertFalse( $outer['isInner'] );
		$this->assertTrue( $inner['isInner'] );
		$this->assertSame( 'heading', $head['widgetType'] );
		$this->assertFalse( $head['isInner'] );
		$this->assertSame( array(), $outer['settings'] );
		foreach ( $created as $id ) {
			$this->assertMatchesRegularExpression( '/^[0-9a-f]{7}$/', $id );
		}
	}

	public function test_insert_into_container_at_position(): void {
		$tree    = $this->page();
		$created = $tree->insert( array( array( 'elType' => 'widget', 'widgetType' => 'image' ) ), 'c1', 0, new Ids( $tree->ids() ) );
		$this->assertSame( array( $created[0], 'c2', 't1' ), $tree->children( 'c1' ) );
	}

	public function test_remove_returns_subtree_ids(): void {
		$tree = $this->page();
		$this->assertSame( array( 'c2', 'h1' ), $tree->remove( 'c2' ) );
		$this->assertSame( array( 't1' ), $tree->children( 'c1' ) );
		$this->assertFalse( $tree->has( 'h1' ) );
	}

	public function test_move_between_parents_updates_inner_flag(): void {
		$tree = $this->page();
		$tree->move( 'c2', null, 0 );
		$this->assertSame( array( 'c2', 'c1', 'c3' ), $tree->children( null ) );
		$this->assertFalse( $tree->get( 'c2' )['isInner'] );

		$tree->move( 'c2', 'c3', null );
		$this->assertSame( array( 'c2' ), $tree->children( 'c3' ) );
		$this->assertTrue( $tree->get( 'c2' )['isInner'] );
		$this->assertSame( array( 'h1' ), $tree->children( 'c2' ) );
	}

	public function test_reorder_within_parent(): void {
		$tree = $this->page();
		$tree->move( 't1', 'c1', 0 );
		$this->assertSame( array( 't1', 'c2' ), $tree->children( 'c1' ) );
	}

	public function test_move_into_own_descendant_is_refused(): void {
		$this->expectException( TreeError::class );
		$this->page()->move( 'c1', 'c2', 0 );
	}

	public function test_duplicate_copies_subtree_with_new_ids_after_original(): void {
		$tree = $this->page();
		$copy = $tree->duplicate( 'c2', new Ids( $tree->ids() ) );

		$this->assertSame( array( 'c2', $copy['id'], 't1' ), $tree->children( 'c1' ) );
		$this->assertCount( 2, $copy['created'] );
		$this->assertSame( 'Hi', $tree->get( $copy['created'][1] )['settings']['title'] );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_update_merges_removes_and_replaces(): void {
		$tree = $this->page();
		$tree->update( 'h1', array( 'header_size' => 'h2', 'title' => null ) );
		$this->assertSame( array( 'header_size' => 'h2' ), $tree->get( 'h1' )['settings'] );

		$tree->update( 'h1', array( 'title' => 'Only' ), 'replace' );
		$this->assertSame( array( 'title' => 'Only' ), $tree->get( 'h1' )['settings'] );
	}

	public function test_update_merges_global_references(): void {
		$tree = $this->page();
		$tree->update( 'h1', array( '__globals__' => array( 'title_color' => 'globals/colors?id=primary' ) ) );
		$tree->update( 'h1', array( '__globals__' => array( 'typography_typography' => 'globals/typography?id=accent' ) ) );
		$this->assertSame(
			array(
				'title_color'           => 'globals/colors?id=primary',
				'typography_typography' => 'globals/typography?id=accent',
			),
			$tree->get( 'h1' )['settings']['__globals__']
		);

		$tree->update( 'h1', array( '__globals__' => array( 'title_color' => null ) ) );
		$this->assertSame( array( 'typography_typography' => 'globals/typography?id=accent' ), $tree->get( 'h1' )['settings']['__globals__'] );
	}

	public function test_unknown_element_reports_how_to_fix(): void {
		try {
			$this->page()->get( 'nope' );
			$this->fail( 'Expected TreeError.' );
		} catch ( TreeError $error ) {
			$this->assertSame( 'element_not_found', $error->error_code );
			$this->assertStringContainsString( 'elementor-get-page', $error->fix );
		}
	}

	public function test_outline_limits_depth_and_marks_atomic(): void {
		$tree = new Tree(
			array(
				array( 'id' => 'a1', 'elType' => 'e-flexbox', 'settings' => array(), 'elements' => array() ),
			)
		);
		$this->assertTrue( $tree->outline()[0]['atomic'] );
		$this->assertSame( 'atomic', $tree->structure() );

		$outline = $this->page()->outline( null, 1 );
		$this->assertSame( 2, $outline[0]['childCount'] );
		$this->assertArrayNotHasKey( 'children', $outline[0] );
	}

	public function test_structure_detects_sections_and_mixed(): void {
		$sections = new Tree( array( array( 'id' => 's1', 'elType' => 'section', 'elements' => array() ) ) );
		$this->assertSame( 'sections', $sections->structure() );

		$mixed = new Tree(
			array(
				array( 'id' => 's1', 'elType' => 'section', 'elements' => array() ),
				array( 'id' => 'c1', 'elType' => 'container', 'elements' => array() ),
			)
		);
		$this->assertSame( 'mixed', $mixed->structure() );
		$this->assertSame( 'empty', ( new Tree( array() ) )->structure() );
	}

	public function test_integrity_reports_duplicate_ids_and_skips_malformed(): void {
		$tree = new Tree(
			array(
				array( 'id' => 'x1', 'elType' => 'container', 'elements' => array( array( 'id' => 'x1', 'elType' => 'widget', 'widgetType' => 'heading' ) ) ),
				array( 'elType' => 'container' ),
				'junk',
			)
		);
		$this->assertSame( 2, $tree->count() );
		$this->assertSame( array( 'Element ID x1 is used more than once.' ), $tree->integrity() );
	}

	public function test_diff_of_flat_views(): void {
		$tree   = $this->page();
		$before = $tree->flat();
		$tree->update( 'h1', array( 'title' => 'Changed' ) );
		$tree->remove( 'c3' );
		$created = $tree->insert( array( array( 'elType' => 'widget', 'widgetType' => 'image' ) ), 'c1', null, new Ids( $tree->ids() ) );

		$diff = Diff::between( $before, $tree->flat() );
		$this->assertSame( $created, $diff['added'] );
		$this->assertSame( array( 'c3' ), $diff['removed'] );
		$this->assertSame( array( 'c1', 'h1' ), $diff['changed'] );
	}

	public function test_ids_are_unique_hex_and_never_digit_only(): void {
		$ids  = new Ids( array( 'aaaaaaa' ) );
		$seen = array();
		for ( $i = 0; $i < 300; $i++ ) {
			$id = $ids->next();
			$this->assertMatchesRegularExpression( '/^[0-9a-f]{7}$/', $id );
			$this->assertFalse( ctype_digit( $id ) );
			$this->assertArrayNotHasKey( $id, $seen );
			$seen[ $id ] = true;
		}
	}
}
