<?php
/**
 * Shared write flow of Bricks page tools.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Bricks;

use AcMcp\Support\Diff;
use AcMcp\Bricks\ClassPromoter;
use AcMcp\Bricks\GlobalClasses;
use AcMcp\Bricks\Store;
use AcMcp\Bricks\Tree;
use AcMcp\Support\TreeError;
use AcMcp\Bricks\Validator;
use AcMcp\Safety\Governance;
use AcMcp\Support\Errors;
use AcMcp\Support\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Every page edit runs the same steps: load, check the editor lock, apply
 * the change in memory, check the tree, then either return a preview (dry
 * run) or snapshot the page and save it the way Bricks does.
 */
abstract class BricksWriteAbility extends BricksAbility {

	/**
	 * Applies the change to the tree. Throw TreeError to refuse.
	 *
	 * @param array<string, mixed> $input Tool input.
	 * @return array<string, mixed> Extra fields for the result.
	 */
	abstract protected function mutate( Tree $tree, array $input, Validator $validator ): array;

	/**
	 * Tool-specific input properties.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	abstract protected function change_properties(): array;

	/**
	 * @return list<string>
	 */
	protected function change_required(): array {
		return array();
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function properties(): array {
		return array( 'postId' => self::post_id_property() )
			+ $this->change_properties()
			+ array(
				'dryRun' => self::dry_run_property(),
				'force'  => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => 'Save even if someone has the page open in an editor. Only use when the user explicitly asks.',
				),
			);
	}

	protected function required(): array {
		return array_merge( array( 'postId' ), $this->change_required() );
	}

	protected function run( array $input ) {
		$loaded = $this->load( (int) $input['postId'] );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}
		[ 'post' => $post, 'key' => $key, 'tree' => $tree ] = $loaded;

		$locked_by = Governance::locked_by( $post->ID );
		if ( $locked_by && ! $input['force'] && 'deny' === $this->settings->get( 'post_lock' ) ) {
			return Errors::make(
				'post_locked',
				/* translators: %s: user name. */
				sprintf( __( '%s has this page open in an editor, so saving now could overwrite their work or be overwritten.', 'ac-mcp-for-bricks-elementor' ), $locked_by ),
				__( 'Ask the user to close the editor, or retry with force: true if they confirm.', 'ac-mcp-for-bricks-elementor' ),
				409
			);
		}

		// Plain classes are invisible in the Bricks class selector; attach them as global classes.
		$classes = new ClassPromoter();
		if ( isset( $input['elements'] ) ) {
			$input['elements'] = $classes->specs( $input['elements'] );
		}
		if ( isset( $input['settings'] ) && is_array( $input['settings'] ) ) {
			$input['settings'] = $classes->settings( $input['settings'] );
		}

		$before = $tree->to_array();
		try {
			$extra = $this->mutate( $tree, $input, new Validator( $this->catalog() ) );
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$problems = $tree->integrity();
		if ( $problems ) {
			return Errors::make( 'broken_tree', __( 'The change would leave the page structure inconsistent: ', 'ac-mcp-for-bricks-elementor' ) . implode( ' ', array_slice( $problems, 0, 5 ) ), __( 'Call bricks-get-page and try a simpler change.', 'ac-mcp-for-bricks-elementor' ), 500 );
		}

		$result = array(
			'postId' => $post->ID,
			'diff'   => Diff::between( $before, $tree->to_array() ),
		) + $extra;
		if ( $classes->pending() ) {
			$result['globalClassesCreated'] = array_column( $classes->pending(), 'name' );
		}

		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot_id = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		$saved       = Store::write( $post->ID, $key, $tree->to_array() );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		if ( $classes->pending() ) {
			$this->snapshots->take_option( GlobalClasses::OPTION, $this->name(), $this->label() );
			$classes->save();
		}

		return array(
			'dryRun'          => false,
			'snapshotId'      => $snapshot_id,
			'builderEditLink' => Posts::builder_edit_link( $post, 'bricks' ),
		) + $result;
	}

	/**
	 * Name of the element that will contain new children, or null for root.
	 *
	 * @param mixed $parent_id Parent element ID input.
	 */
	protected static function parent_name( Tree $tree, $parent_id ): ?string {
		return self::is_root( $parent_id ) ? null : (string) $tree->get( (string) $parent_id )['name'];
	}

	/**
	 * @param mixed $parent_id Parent element ID input.
	 */
	protected static function is_root( $parent_id ): bool {
		return null === $parent_id || in_array( $parent_id, array( '', '0', 0, 'root' ), true );
	}
}
