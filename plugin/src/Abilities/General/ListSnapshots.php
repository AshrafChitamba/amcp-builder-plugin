<?php
/**
 * The list-snapshots tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\General;

use AmcpBuilder\Abilities\Ability;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the automatic backups taken before changes.
 */
final class ListSnapshots extends Ability {

	public function slug(): string {
		return 'list-snapshots';
	}

	public function label(): string {
		return __( 'List snapshots', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Lists snapshots: automatic backups taken right before every change. Filter by postId to see one page\'s history (newest first). '
			. 'Use the id with restore-snapshot to undo a change. Global-data snapshots (e.g. Bricks global classes) have targetType "option".';
	}

	protected function properties(): array {
		return array(
			'postId' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'limit'  => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 50,
				'default' => 20,
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function authorize( array $input ): bool {
		return empty( $input['postId'] ) ? current_user_can( 'edit_theme_options' ) : current_user_can( 'edit_post', (int) $input['postId'] );
	}

	protected function run( array $input ): array {
		return array( 'snapshots' => $this->snapshots->list( (int) ( $input['postId'] ?? 0 ), (int) $input['limit'] ) );
	}
}
