<?php
/**
 * Admin notices for unmet requirements.
 *
 * @package AcMcp
 */

namespace AcMcp\Admin;

use AcMcp\Mcp\Server;
use AcMcp\Migration;
use AcMcp\Requirements;

defined( 'ABSPATH' ) || exit;

/**
 * Warns administrators when AC MCP for Bricks and Elementor cannot run.
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
					esc_html__( 'MCP Builder is now AC MCP for Bricks and Elementor.', 'ac-mcp-for-bricks-elementor' ),
					esc_html__( 'Your settings, snapshots, audit log and connected apps were copied over. Deactivate the old MCP Builder plugin, then update the server URL in each AI assistant to:', 'ac-mcp-for-bricks-elementor' ),
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
				esc_html__( 'AC MCP for Bricks and Elementor is not running yet.', 'ac-mcp-for-bricks-elementor' ),
				$items,
				esc_url( AdminPage::url( 'status' ) ),
				esc_html__( 'Open Status', 'ac-mcp-for-bricks-elementor' )
			),
			array(
				'type'           => 'error',
				'paragraph_wrap' => false,
			)
		);
	}
}
