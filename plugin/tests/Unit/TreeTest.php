<?php
/**
 * @package McpBuilder
 */

namespace McpBuilder\Tests\Unit;

use McpBuilder\Support\Diff;
use McpBuilder\Bricks\Ids;
use McpBuilder\Bricks\Tree;
use McpBuilder\Support\TreeError;
use McpBuilder\Tests\TestCase;

final class TreeTest extends TestCase {

	/**
	 * section(s1) > container(c1) > [heading(h1), text(t1)]; section(s2).
	 */
	private function page(): Tree {
		return new Tree(
			array(
				array( 'id' => 's1', 'name' => 'section', 'parent' => 0, 'children' => array( 'c1' ), 'settings' => array() ),
				array( 'id' => 'c1', 'name' => 'container', 'parent' => 's1', 'children' => array( 'h1', 't1' ), 'settings' => array() ),
				array( 'id' => 'h1', 'name' => 'heading', 'parent' => 'c1', 'children' => array(), 'settings' => array( 'text' => 'Hi' ) ),
				array( 'id' => 't1', 'name' => 'text-basic', 'parent' => 'c1', 'children' => array(), 'settings' => array( 'text' => 'Body' ) ),
				array( 'id' => 's2', 'name' => 'section', 'parent' => 0, 'children' => array(), 'settings' => array() ),
			)
		);
	}

	private function two_column_section(): array {
		return array(
			'name'     => 'section',
			'children' => array(
				array(
					'name'     => 'container',
					'settings' => array( '_display' => 'grid', '_gridTemplateColumns' => '1fr 1fr' ),
					'children' => array(
						array(
							'name'     => 'block',
							'children' => array(
								array( 'name' => 'heading', 'settings' => array( 'text' => 'Title' ) ),
								array( 'name' => 'text-basic', 'settings' => array( 'text' => 'Copy' ) ),
							),
						),
						array( 'name' => 'image', 'settings' => array( 'image' => array( 'id' => 5, 'url' => 'https://x.test/a.jpg' ) ) ),
					),
				),
			),
		);
	}

	public function test_reads_roots_children_and_descendants(): void {
		$tree = $this->page();
		$this->assertSame( array( 's1', 's2' ), $tree->children( 0 ) );
		$this->assertSame( array( 'h1', 't1' ), $tree->children( 'c1' ) );
		$this->assertSame( array( 'c1', 'h1', 't1' ), $tree->descendants( 's1' ) );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_insert_nested_specs_at_root_position(): void {
		$tree    = $this->page();
		$created = $tree->insert( array( $this->two_column_section() ), 0, 1, new Ids( $tree->ids() ) );

		$this->assertCount( 6, $created );
		$this->assertSame( array( 's1', $created[0], 's2' ), $tree->children( 0 ), 'New section sits between the two existing ones.' );
		$this->assertSame( array(), $tree->integrity() );

		$container = $tree->get( $created[1] );
		$this->assertSame( $created[0], $container['parent'] );
		$this->assertSame( '1fr 1fr', $container['settings']['_gridTemplateColumns'] );
		$this->assertCount( 2, $container['children'] );

		foreach ( $created as $id ) {
			$this->assertMatchesRegularExpression( '/^[a-z][a-z0-9]{5}$/', $id );
		}
	}

	public function test_insert_into_parent_appends_or_positions(): void {
		$tree = $this->page();
		[ $first ] = $tree->insert( array( array( 'name' => 'heading' ) ), 'c1', 0, new Ids( $tree->ids() ) );
		[ $last ]  = $tree->insert( array( array( 'name' => 'heading' ) ), 'c1', null, new Ids( $tree->ids() ) );

		$this->assertSame( array( $first, 'h1', 't1', $last ), $tree->children( 'c1' ) );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_insert_into_missing_parent_fails(): void {
		$this->expectException( TreeError::class );
		$this->page()->insert( array( array( 'name' => 'heading' ) ), 'nope', null, new Ids() );
	}

	public function test_remove_deletes_subtree(): void {
		$tree    = $this->page();
		$removed = $tree->remove( 's1' );

		$this->assertSame( array( 's1', 'c1', 'h1', 't1' ), $removed );
		$this->assertSame( array( 's2' ), $tree->ids() );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_remove_child_updates_parent(): void {
		$tree = $this->page();
		$tree->remove( 'h1' );
		$this->assertSame( array( 't1' ), $tree->children( 'c1' ) );
	}

	public function test_move_between_parents_and_to_root(): void {
		$tree = $this->page();
		$tree->move( 't1', 's2', null );
		$this->assertSame( array( 'h1' ), $tree->children( 'c1' ) );
		$this->assertSame( array( 't1' ), $tree->children( 's2' ) );

		$tree->move( 'c1', 0, 0 );
		$this->assertSame( array( 'c1', 's1', 's2' ), $tree->children( 0 ) );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_reorder_within_parent(): void {
		$tree = $this->page();
		$tree->move( 't1', 'c1', 0 );
		$this->assertSame( array( 't1', 'h1' ), $tree->children( 'c1' ) );
	}

	public function test_cannot_move_into_own_descendant(): void {
		$this->expectException( TreeError::class );
		$this->page()->move( 's1', 'c1', null );
	}

	public function test_duplicate_copies_subtree_after_original(): void {
		$tree = $this->page();
		$copy = $tree->duplicate( 's1', new Ids( $tree->ids() ) );

		$this->assertSame( array( 's1', $copy['id'], 's2' ), $tree->children( 0 ) );
		$this->assertCount( 4, $copy['created'] );
		$this->assertSame( 'Hi', $tree->get( $copy['created'][2] )['settings']['text'] );
		$this->assertSame( array(), array_intersect( $copy['created'], array( 's1', 'c1', 'h1', 't1' ) ) );
		$this->assertSame( array(), $tree->integrity() );
	}

	public function test_update_merges_and_removes_null_keys(): void {
		$tree = $this->page();
		$tree->update( 'h1', array( 'tag' => 'h2', 'text' => null ), 'merge', 'Hero title' );

		$element = $tree->get( 'h1' );
		$this->assertSame( array( 'tag' => 'h2' ), $element['settings'] );
		$this->assertSame( 'Hero title', $element['label'] );

		$tree->update( 'h1', array( 'text' => 'New' ), 'replace', '' );
		$this->assertSame( array( 'text' => 'New' ), $tree->get( 'h1' )['settings'] );
		$this->assertArrayNotHasKey( 'label', $tree->get( 'h1' ) );
	}

	public function test_update_missing_element_fails(): void {
		$this->expectException( TreeError::class );
		$this->page()->update( 'zzz', array(), 'replace' );
	}

	public function test_outline_nests_and_limits_depth(): void {
		$outline = $this->page()->outline( null, 2 );
		$this->assertSame( 's1', $outline[0]['id'] );
		$this->assertSame( 'c1', $outline[0]['children'][0]['id'] );
		$this->assertSame( 2, $outline[0]['children'][0]['childCount'] );
	}

	public function test_integrity_reports_broken_links(): void {
		$tree = new Tree(
			array(
				array( 'id' => 'a', 'name' => 'section', 'parent' => 0, 'children' => array( 'b', 'ghost' ) ),
				array( 'id' => 'b', 'name' => 'heading', 'parent' => 'zzz', 'children' => array() ),
			)
		);
		$this->assertCount( 3, $tree->integrity() );
	}

	public function test_diff_lists_added_removed_and_changed(): void {
		$tree   = $this->page();
		$before = $tree->to_array();
		$tree->update( 'h1', array( 'text' => 'Changed' ) );
		$tree->remove( 's2' );
		[ $new ] = $tree->insert( array( array( 'name' => 'heading' ) ), 'c1', null, new Ids( $tree->ids() ) );

		$diff = Diff::between( $before, $tree->to_array() );
		$this->assertSame( array( $new ), $diff['added'] );
		$this->assertSame( array( 's2' ), $diff['removed'] );
		$this->assertSame( array( 'c1', 'h1' ), $diff['changed'] );
	}

	public function test_ids_are_unique(): void {
		$ids  = new Ids( array( 'aaaaaa' ) );
		$seen = array();
		for ( $i = 0; $i < 500; $i++ ) {
			$seen[] = $ids->next();
		}
		$this->assertCount( 500, array_unique( $seen ) );
		$this->assertNotContains( 'aaaaaa', $seen );
	}

	public function test_root_placeholder_in_custom_css_is_resolved(): void {
		$tree = $this->page();
		[ $id ] = $tree->insert( array( array( 'name' => 'heading', 'settings' => array( '_cssCustom' => '%root% { color: red; }' ) ) ), 'c1', null, new Ids( $tree->ids() ) );
		$this->assertSame( "#brxe-{$id} { color: red; }", $tree->get( $id )['settings']['_cssCustom'] );

		$tree->update( 'h1', array( '_cssCustom:mobile_portrait' => '%root%:hover { color: blue; }' ) );
		$this->assertSame( '#brxe-h1:hover { color: blue; }', $tree->get( 'h1' )['settings']['_cssCustom:mobile_portrait'] );
	}

	public function test_digit_only_ids_are_handled_as_strings(): void {
		$tree = new Tree(
			array(
				array( 'id' => '266556', 'name' => 'section', 'parent' => 0, 'children' => array( '374f2b' ) ),
				array( 'id' => '374f2b', 'name' => 'block', 'parent' => '266556', 'children' => array( '677186' ) ),
				array( 'id' => '677186', 'name' => 'heading', 'parent' => '374f2b', 'children' => array() ),
			)
		);
		$this->assertSame( array(), $tree->integrity() );
		$this->assertSame( array( '374f2b', '677186' ), $tree->descendants( '266556' ) );

		$tree->move( '677186', 0, 0 );
		$this->assertSame( array( '677186', '266556' ), $tree->children( 0 ) );
		$this->assertSame( array(), $tree->integrity() );
		$this->assertSame( array( '266556', '374f2b' ), $tree->remove( '266556' ) );
	}
}
