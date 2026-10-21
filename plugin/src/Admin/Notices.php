<?php
/**
 * Admin notices for unmet requirements.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Admin;

use AmcpBuilder\Mcp\Server;
use AmcpBuilder\Migration;
use AmcpBuilder\Requirements;

defined( 'ABSPATH' ) || exit;

/**
 * Warns administrators when AMCP Builder cannot run.
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

		if ( Migration::old_plugin_active() ) {
			wp_admin_notice(
				sprintf(
					'<p><strong>%1$s</strong> %2$s</p><p><code>%3$s</code></p>',
					esc_html__( 'MCP Builder is now AMCP Builder.', 'amcp-builder' ),
					esc_html__( 'Your settings, snapshots, audit log and connected apps were copied over. Deactivate the old MCP Builder plugin, then update the server URL in each AI assistant to:', 'amcp-builder' ),
					esc_html( Server::url() )
				),
				array(
					'type'           => 'warning',
					'paragraph_wrap' => false,
				)
			);
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
				esc_html__( 'AMCP Builder is not running yet.', 'amcp-builder' ),
				$items,
				esc_url( AdminPage::url( 'status' ) ),
				esc_html__( 'Open Status', 'amcp-builder' )
			),
			array(
				'type'           => 'error',
				'paragraph_wrap' => false,
			)
		);
	}
}
