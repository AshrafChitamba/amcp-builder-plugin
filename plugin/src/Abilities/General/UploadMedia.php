<?php
/**
 * The upload-media tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\General;

use McpBuilder\Abilities\Ability;
use McpBuilder\Support\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an image from a public URL to the Media Library.
 */
final class UploadMedia extends Ability {

	private const MAX_BYTES = 10485760; // 10 MB.

	private const ALLOWED_TYPES = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif' );

	public function slug(): string {
		return 'upload-media';
	}

	public function label(): string {
		return __( 'Upload media from URL', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Downloads an image (JPEG, PNG, GIF, WebP or AVIF, up to 10 MB) from a public https URL into the Media Library and returns its attachment ID and URL for use in image elements. '
			. 'Only use URLs the user gave you or images you are allowed to use; always set alt text that describes the image. Check list-media first to avoid duplicates.';
	}

	protected function properties(): array {
		return array(
			'url'     => array(
				'type'      => 'string',
				'format'    => 'uri',
				'pattern'   => '^https://',
				'maxLength' => 2000,
			),
			'title'   => array(
				'type'      => 'string',
				'maxLength' => 200,
			),
			'altText' => array(
				'type'      => 'string',
				'maxLength' => 300,
			),
			'dryRun'  => self::dry_run_property(),
		);
	}

	protected function required(): array {
		return array( 'url', 'altText' );
	}

	public function is_readonly(): bool {
		return false;
	}

	protected function authorize( array $input ): bool {
		return current_user_can( 'upload_files' );
	}

	protected function run( array $input ) {
		$url = esc_url_raw( (string) $input['url'], array( 'https' ) );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return Errors::make( 'invalid_url', __( 'The image URL is not a valid public https URL.', 'mcp-builder' ), __( 'Use a direct https link to the image file.', 'mcp-builder' ) );
		}

		if ( $this->is_dry_run( $input ) ) {
			return array(
				'dryRun'     => true,
				'wouldFetch' => $url,
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// download_url() uses wp_safe_remote_get(), which refuses private and local addresses.
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return Errors::make( 'download_failed', $tmp->get_error_message(), __( 'Check that the URL is public and points directly to an image.', 'mcp-builder' ), 502 );
		}

		$size = (int) filesize( $tmp );
		$type = wp_get_image_mime( $tmp );
		if ( $size > self::MAX_BYTES || ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			wp_delete_file( $tmp );
			return Errors::make( 'unsupported_file', __( 'The file is not a supported image or is larger than 10 MB.', 'mcp-builder' ), __( 'Use a JPEG, PNG, GIF, WebP or AVIF image under 10 MB.', 'mcp-builder' ) );
		}

		$extension = wp_get_default_extension_for_mime_type( $type );
		$name      = sanitize_file_name( pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME ) );
		$file      = array(
			'name'     => ( '' !== $name ? $name : 'image' ) . '.' . $extension,
			'tmp_name' => $tmp,
		);

		$id = media_handle_sideload( $file, 0, isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : null );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return Errors::make( 'upload_failed', $id->get_error_message(), '', 500 );
		}

		update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $input['altText'] ) );
		$meta = wp_get_attachment_metadata( $id );

		return array(
			'dryRun'   => false,
			'id'       => $id,
			'url'      => (string) wp_get_attachment_url( $id ),
			'filename' => wp_basename( (string) get_attached_file( $id ) ),
			'width'    => (int) ( $meta['width'] ?? 0 ),
			'height'   => (int) ( $meta['height'] ?? 0 ),
		);
	}
}
