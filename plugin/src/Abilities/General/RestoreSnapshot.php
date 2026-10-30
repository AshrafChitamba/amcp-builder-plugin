<?php
/**
 * The restore-snapshot tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\General;

use AcMcp\Abilities\Ability;
use AcMcp\Safety\Governance;
use AcMcp\Safety\Snapshots;
use AcMcp\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Puts a page (or global data) back to a snapshot.
 */
final class RestoreSnapshot extends Ability {

	public function slug(): string {
		return 'restore-snapshot';
	}

	public function label(): string {
		return __( 'Restore snapshot', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Restores a page (or global data such as Bricks global classes) to the state saved in a snapshot, undoing every change made after it. '
			. 'The current state is snapshotted first, so the restore can be undone too (undoSnapshotId). Find IDs with list-snapshots. Confirm with the user before restoring.';
	}

	protected function properties(): array {
		return array(
			'snapshotId' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'dryRun'     => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'snapshotId' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function is_destructive(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		$snapshot = $this->snapshots->get( (int) $input['snapshotId'] );
		if ( ! $snapshot ) {
			return current_user_can( 'edit_posts' );
		}
		return Snapshots::TYPE_POST === $snapshot['targetType']
			? current_user_can( 'edit_post', (int) $snapshot['targetId'] )
			: current_user_can( 'edit_theme_options' );
	}

	protected function run( array $input ) {
		$snapshot = $this->snapshots->get( (int) $input['snapshotId'] );
		if ( ! $snapshot ) {
			return Errors::make( 'snapshot_not_found', __( 'That snapshot does not exist or has expired.', 'ac-mcp-for-bricks-elementor' ), __( 'Call list-snapshots to see available snapshots.', 'ac-mcp-for-bricks-elementor' ), 404 );
		}
		unset( $snapshot['payload'] );

		$locked_by = Snapshots::TYPE_POST === $snapshot['targetType'] ? Governance::locked_by( (int) $snapshot['targetId'] ) : null;
		if ( $locked_by && 'deny' === $this->settings->get( 'post_lock' ) ) {
			return Errors::make(
				'post_locked',
				/* translators: %s: user name. */
				sprintf( __( '%s has this page open in an editor.', 'ac-mcp-for-bricks-elementor' ), $locked_by ),
				__( 'Ask the user to close the editor, then try again.', 'ac-mcp-for-bricks-elementor' ),
				409
			);
		}

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'       => true,
				'wouldRestore' => $snapshot,
			);
		}

		$result = $this->snapshots->restore( (int) $input['snapshotId'], $this->name() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'dryRun'     => false,
			'snapshotId' => $result['undoSnapshotId'],
			'restored'   => $snapshot,
		) + $result;
	}
}
