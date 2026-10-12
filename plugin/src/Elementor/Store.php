<?php
/**
 * Reads and writes Elementor documents.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Elementor;

use McpBuilder\Support\Errors;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Saves through Elementor's own Document::save(), the same path the editor
 * uses: capability check, kses for users without unfiltered_html, element
 * instances built from the registry, version and template type meta, and
 * the post CSS file cleared so it is regenerated on the next view.
 *
 * VERIFY: Documents_Manager::get( $id, false ), Document::save( [ 'elements' => …, 'settings' => … ] ),
 * get_elements_data(), is_editable_by_current_user() and set_is_built_with_elementor() (Elementor 4.3.4 core/base/document.php).
 */
final class Store {

	public static function available(): bool {
		return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents );
	}

	/**
	 * Fresh (uncached) document for a post, or null when Elementor cannot handle it.
	 *
	 * @return \Elementor\Core\Base\Document|null
	 */
	public static function document( int $post_id ) {
		$document = \Elementor\Plugin::$instance->documents->get( $post_id, false );
		return $document ? $document : null;
	}

	public static function can_edit( int $post_id ): bool {
		$document = self::document( $post_id );
		return $document && $document->is_editable_by_current_user();
	}

	/**
	 * Stored elements of the published version.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function read( int $post_id ): array {
		$document = self::document( $post_id );
		$elements = $document ? $document->get_elements_data() : array();
		return is_array( $elements ) ? array_values( $elements ) : array();
	}

	/**
	 * Page settings (_elementor_page_settings).
	 *
	 * @return array<string, mixed>
	 */
	public static function read_settings( int $post_id ): array {
		$settings = get_post_meta( $post_id, '_elementor_page_settings', true );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * @param array{elements?: list<array<string, mixed>>, settings?: array<string, mixed>} $data What to save.
	 * @return true|WP_Error
	 */
	public static function write( int $post_id, array $data ) {
		$document = self::document( $post_id );
		if ( ! $document ) {
			return Errors::make( 'elementor_unsupported', __( 'Elementor cannot edit this post.', 'mcp-builder' ), __( 'Check that the post type is enabled in Elementor → Settings.', 'mcp-builder' ), 400 );
		}
		if ( ! $document->is_editable_by_current_user() ) {
			return Errors::make( 'elementor_access_denied', __( 'Elementor does not allow your user to edit this page.', 'mcp-builder' ), __( 'Your role needs access to Elementor (Elementor → Role Manager) and edit rights for the page.', 'mcp-builder' ), 403 );
		}

		if ( ! $document->save( $data ) ) {
			return Errors::make( 'elementor_save_failed', __( 'Elementor refused to save the page.', 'mcp-builder' ), __( 'Your user may lack edit rights, or another plugin blocked the save.', 'mcp-builder' ), 500 );
		}
		$document->set_is_built_with_elementor( true );
		clean_post_cache( $post_id );

		if ( isset( $data['elements'] ) ) {
			$expected = ( new Tree( $data['elements'] ) )->count();
			$stored   = ( new Tree( self::read( $post_id ) ) )->count();
			if ( $stored !== $expected ) {
				return Errors::make(
					'elementor_write_incomplete',
					/* translators: 1: saved element count, 2: expected element count. */
					sprintf( __( 'Elementor saved %1$d of %2$d elements.', 'mcp-builder' ), $stored, $expected ),
					__( 'A widget may belong to a plugin that is no longer active. Call elementor-get-page to see what was kept, or restore-snapshot to undo.', 'mcp-builder' ),
					500
				);
			}
		}
		return true;
	}
}
