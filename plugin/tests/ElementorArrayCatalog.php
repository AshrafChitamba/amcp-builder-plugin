<?php
/**
 * In-memory Elementor catalog for tests.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Tests;

use McpBuilder\Elementor\Catalog;

final class ElementorArrayCatalog implements Catalog {

	private const LAYOUT = array( 'padding', 'padding_mobile', 'flex_direction', 'flex_direction_mobile', 'background_background', 'background_color', 'content_width' );

	private const WIDGETS = array(
		'heading'     => array( 'title', 'header_size', 'title_color', 'typography_typography', 'typography_font_size' ),
		'text-editor' => array( 'editor' ),
		'image'       => array( 'image', 'image_size' ),
		'nested-tabs' => array( 'tabs' ),
	);

	public function exists( string $el_type, ?string $widget_type ): bool {
		return 'widget' === $el_type ? isset( self::WIDGETS[ (string) $widget_type ] ) : in_array( $el_type, array( 'container', 'section', 'column' ), true );
	}

	public function accepts_children( string $el_type, ?string $widget_type ): bool {
		return 'widget' !== $el_type || 'nested-tabs' === $widget_type;
	}

	public function setting_keys( string $el_type, ?string $widget_type ): array {
		$common = array( '_margin', '_element_id', '_css_classes' );
		return 'widget' === $el_type ? array_merge( self::WIDGETS[ (string) $widget_type ] ?? array(), $common ) : self::LAYOUT;
	}
}
