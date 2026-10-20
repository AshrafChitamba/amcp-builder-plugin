<?php
/**
 * The bricks-code-status tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

use AmcpBuilder\Bricks\Store;
use AmcpBuilder\Support\CustomCode;
use AmcpBuilder\Support\Untrusted;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Reports whether Bricks custom code can run, and which code still needs signing.
 */
final class CodeStatus extends BricksAbility {

	public function slug(): string {
		return 'bricks-code-status';
	}

	public function label(): string {
		return __( 'Bricks: code status', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Reports whether Bricks custom code can run on this site: code execution setting, whether the current user may execute code, whether AMCP Builder allows custom code, '
			. 'and every Code or SVG-code element with its signature status (valid, invalid or missing). Code that runs PHP or HTML only executes after an administrator signs it in Bricks → Settings → Custom code → "Regenerate code signatures". '
			. 'AMCP Builder never signs code itself. Use it before and after adding a Code element, and tell the user what they must do.';
	}

	protected function properties(): array {
		return array(
			'postId' => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Only this page or template. Omit to scan up to 200 Bricks pages and templates.',
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		$ids = empty( $input['postId'] )
			? ( new WP_Query(
				array(
					'post_type'      => 'any',
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Bounded scan, ids only.
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Finding Bricks content.
						'relation' => 'OR',
						array(
				'key'     => Store::CONTENT,
				'compare' => 'EXISTS',
					),
						array(
					'key'     => Store::HEADER,
					'compare' => 'EXISTS',
					),
						array(
					'key'     => Store::FOOTER,
					'compare' => 'EXISTS',
					),
					),
				)
			) )->posts
			: array( (int) $input['postId'] );

		$elements = array();
		foreach ( $ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			foreach ( Store::AREA_KEYS as $key ) {
				foreach ( Store::read( (int) $post_id, $key ) as $element ) {
					$settings = (array) ( $element['settings'] ?? array() );
					$code     = 'code' === ( $element['name'] ?? '' ) ? ( $settings['code'] ?? null ) : ( 'svg' === ( $element['name'] ?? '' ) ? ( $settings['code'] ?? null ) : null );
					if ( null === $code ) {
						continue;
					}
					$signature  = (string) ( $settings['signature'] ?? '' );
					$elements[] = array(
						'postId'    => (int) $post_id,
						'postTitle' => Untrusted::text( get_the_title( (int) $post_id ), 80 ),
						'elementId' => (string) ( $element['id'] ?? '' ),
						'type'      => (string) $element['name'],
						'executes'  => ! empty( $settings['executeCode'] ) || 'svg' === $element['name'],
						// Bricks signatures are wp_hash() of the code (Bricks 2.3.1 helpers.php verify_code_signature).
						'signature' => '' === $signature ? 'missing' : ( hash_equals( wp_hash( (string) $code ), $signature ) ? 'valid' : 'invalid' ),
					);
				}
			}
		}

		$enabled = class_exists( '\Bricks\Helpers' ) && method_exists( '\Bricks\Helpers', 'code_execution_enabled' ) && \Bricks\Helpers::code_execution_enabled();
		$can_run = class_exists( '\Bricks\Capabilities' ) && method_exists( '\Bricks\Capabilities', 'current_user_can_execute_code' ) && \Bricks\Capabilities::current_user_can_execute_code();

		return array(
			'codeExecutionEnabled'  => $enabled,
			'userCanExecuteCode'    => $can_run,
			'amcpBuilderAllowsCode' => CustomCode::allowed( $this->settings ),
			'signaturesLocked'      => defined( 'BRICKS_LOCK_CODE_SIGNATURES' ) && BRICKS_LOCK_CODE_SIGNATURES,
			'codeElements'          => $elements,
			'needsSigning'          => count( array_filter( $elements, static fn( $e ): bool => $e['executes'] && 'valid' !== $e['signature'] ) ),
			'reviewUrl'             => admin_url( 'admin.php?page=bricks-settings&code-review=all#tab-custom-code' ),
			'howToSign'             => 'An administrator reviews the code at reviewUrl, then clicks "Regenerate code signatures" under Bricks → Settings → Custom code. Code execution must be enabled there too.',
		);
	}
}
