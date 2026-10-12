<?php
/**
 * Admin notices for unmet requirements.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Admin;

use McpBuilder\Requirements;

defined( 'ABSPATH' ) || exit;

/**
 * Warns administrators when MCP Builder cannot run.
 */
final class Notices {

	public function __construct( private Requirements $requirements ) {}

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$failed = array_filter( $this->requirements->core_checks(), static fn( array $check ): bool => 'error' === $check['status'] );
		if ( ! $failed ) {
			return;
		}

		$items = implode(
			'',
			array_map(
				static fn( array $check ): string => '<li>' . esc_html( $check['label'] . ': ' . $check['message'] ) . '</li>',
				$failed
			)
		);

		wp_admin_notice(
			sprintf(
				'<p><strong>%s</strong></p><ul>%s</ul><p><a href="%s">%s</a></p>',
				esc_html__( 'MCP Builder is not running yet.', 'mcp-builder' ),
				$items,
				esc_url( AdminPage::url( 'status' ) ),
				esc_html__( 'Open Status', 'mcp-builder' )
			),
			array(
				'type'           => 'error',
				'paragraph_wrap' => false,
			)
		);
	}
}
