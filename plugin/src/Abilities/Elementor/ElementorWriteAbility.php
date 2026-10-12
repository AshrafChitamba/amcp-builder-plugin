<?php
/**
 * Shared write flow of Elementor page tools.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Elementor;

use McpBuilder\Elementor\Store;
use McpBuilder\Elementor\Tree;
use McpBuilder\Elementor\Validator;
use McpBuilder\Safety\Governance;
use McpBuilder\Support\Diff;
use McpBuilder\Support\Errors;
use McpBuilder\Support\Posts;
use McpBuilder\Support\TreeError;

defined( 'ABSPATH' ) || exit;

/**
 * Every page edit runs the same steps: load, check the editor lock, apply
 * the change in memory, check the tree, then either return a preview (dry
 * run) or snapshot the page and save it through Elementor.
 */
abstract class ElementorWriteAbility extends ElementorAbility {

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
		[ 'post' => $post, 'tree' => $tree ] = $loaded;

		$locked = $this->lock_error( $post->ID, (bool) $input['force'] );
		if ( $locked ) {
			return $locked;
		}

		$before = $tree->flat();
		try {
			$extra = $this->mutate( $tree, $input, new Validator( $this->catalog() ) );
		} catch ( TreeError $error ) {
			return $error->to_wp_error();
		}

		$problems = $tree->integrity();
		if ( $problems ) {
			return Errors::make( 'broken_tree', __( 'The change would leave the page structure inconsistent: ', 'mcp-builder' ) . implode( ' ', array_slice( $problems, 0, 5 ) ), __( 'Call elementor-get-page and try a simpler change.', 'mcp-builder' ), 500 );
		}

		$result = array(
			'postId' => $post->ID,
			'diff'   => Diff::between( $before, $tree->flat() ),
		) + $extra;

		if ( $this->is_dry_run( $input ) ) {
			return array( 'dryRun' => true ) + $result;
		}

		$snapshot_id = $this->snapshots->take_post( $post->ID, $this->name(), $this->label() );
		$saved       = Store::write( $post->ID, array( 'elements' => $tree->to_array() ) );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'dryRun'          => false,
			'snapshotId'      => $snapshot_id,
			'builderEditLink' => Posts::builder_edit_link( $post, 'elementor' ),
			'previewLink'     => (string) get_preview_post_link( $post ),
		) + $result;
	}

	/**
	 * Error when another user has the page open and the site denies such writes.
	 */
	protected function lock_error( int $post_id, bool $force ): ?\WP_Error {
		$locked_by = Governance::locked_by( $post_id );
		if ( ! $locked_by || $force || 'deny' !== $this->settings->get( 'post_lock' ) ) {
			return null;
		}
		return Errors::make(
			'post_locked',
			/* translators: %s: user name. */
			sprintf( __( '%s has this page open in an editor, so saving now could overwrite their work or be overwritten.', 'mcp-builder' ), $locked_by ),
			__( 'Ask the user to close the editor, or retry with force: true if they confirm.', 'mcp-builder' ),
			409
		);
	}

	/**
	 * Parent element for an input parentId, or null for the top level.
	 *
	 * @param mixed $parent_id Parent element ID input.
	 * @return array<string, mixed>|null
	 */
	protected static function parent_element( Tree $tree, $parent_id ): ?array {
		return self::is_root( $parent_id ) ? null : $tree->get( (string) $parent_id );
	}

	/**
	 * @param mixed $parent_id Parent element ID input.
	 */
	protected static function is_root( $parent_id ): bool {
		return null === $parent_id || in_array( $parent_id, array( '', '0', 0, 'root' ), true );
	}
}
