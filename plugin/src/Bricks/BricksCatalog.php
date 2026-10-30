<?php
/**
 * Element catalog read from the live Bricks registry.
 *
 * @package AcMcp
 */

namespace AcMcp\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps Bricks\Elements so tools only ever offer elements and settings that
 * exist on this site, including custom elements from other plugins.
 *
 * VERIFY: Bricks\Elements::$elements and Elements::get_element( [ 'name' => … ] )
 * (Bricks 2.3.1 includes/elements.php) load controls on demand outside the builder.
 */
final class BricksCatalog implements Catalog {

	private const CACHE_KEY = 'acmcp_bricks_catalog_';

	/** Control types that only decorate the panel and hold no value. */
	private const NON_VALUE_TYPES = array( 'separator', 'info', 'apply', 'divider', 'button' );

	/** Short descriptions of how each control type's value is shaped. */
	public const VALUE_SHAPES = array(
		'text'            => 'string',
		'textarea'        => 'string',
		'editor'          => 'HTML string',
		'code'            => 'string',
		'number'          => 'number, or a string with a unit like "24px" or "50%"',
		'select'          => 'one option key as a string (array of keys if multiple)',
		'radio'           => 'one option key as a string',
		'checkbox'        => 'true',
		'color'           => '{"hex":"#1e293b"} or {"raw":"var(--primary)"}',
		'spacing'         => '{"top":"20px","right":"20px","bottom":"20px","left":"20px"}',
		'dimensions'      => '{"top":"20px","right":"20px","bottom":"20px","left":"20px"}',
		'typography'      => '{"font-size":"18px","font-weight":"600","line-height":"1.5","color":{"hex":"#111111"},"text-align":"left"}',
		'background'      => '{"color":{"hex":"#f8fafc"}} or {"image":{"id":123,"url":"https://…","size":"full"},"size":"cover","position":"center center"}',
		'border'          => '{"width":{"top":"1px","right":"1px","bottom":"1px","left":"1px"},"style":"solid","color":{"hex":"#e2e8f0"},"radius":{"top":"8px","right":"8px","bottom":"8px","left":"8px"}}',
		'box-shadow'      => '{"values":{"offsetX":"0","offsetY":"12","blur":"24","spread":"-6"},"color":{"raw":"rgba(0,0,0,0.15)"}}',
		'image'           => '{"id":123,"url":"https://…/image.jpg","size":"full"} (get IDs and URLs from list-media)',
		'link'            => '{"type":"external","url":"https://…","newTab":true} or {"type":"internal","postId":42}',
		'icon'            => '{"library":"themify","icon":"ti-star"}',
		'svg'             => '{"id":123,"url":"https://…/icon.svg"}',
		'align-items'     => 'CSS value, e.g. "center"',
		'justify-content' => 'CSS value, e.g. "space-between"',
		'direction'       => '"row" or "column"',
		'text-align'      => '"left", "center", "right" or "justify"',
		'repeater'        => 'array of objects, each with an "id" and the item fields',
		'query'           => 'object, e.g. {"objectType":"post","post_type":["post"],"posts_per_page":6}',
	);

	/** Hints for settings whose raw CSS value is easy to get wrong. */
	public const KEY_HINTS = array(
		'_gridItemColumnSpan'  => 'raw CSS grid-column value: "span 2" to span two columns ("2" places it on line 2)',
		'_gridItemRowSpan'     => 'raw CSS grid-row value: "span 2" to span two rows ("2" places it on row line 2)',
		'_gridTemplateColumns' => 'raw CSS, e.g. "1fr 1fr" or "repeat(3, minmax(0, 1fr))"',
		'_gridTemplateRows'    => 'raw CSS, e.g. "280px 280px" or "auto"',
	);

	/** @var array<string, array<string, array<string, mixed>>> name => controls */
	private array $controls = array();

	public static function available(): bool {
		return class_exists( '\Bricks\Elements' );
	}

	public function has( string $name ): bool {
		return isset( \Bricks\Elements::$elements[ $name ] );
	}

	public function nestable( string $name ): bool {
		return ! empty( $this->element( $name )['nestable'] );
	}

	public function setting_keys( string $name ): array {
		return array_map( 'strval', array_keys( $this->controls( $name ) ) );
	}

	/**
	 * Every element with label, category and nestability. Cached per Bricks version.
	 *
	 * @return list<array{name: string, label: string, category: string, nestable: bool}>
	 */
	public function all(): array {
		$key    = self::CACHE_KEY . md5( ( defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : '' ) . get_locale() . implode( ',', array_keys( \Bricks\Elements::$elements ) ) );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$list = array();
		foreach ( array_keys( \Bricks\Elements::$elements ) as $name ) {
			$element = $this->element( (string) $name );
			if ( ! empty( $element['deprecated'] ) ) {
				continue;
			}
			$list[] = array(
				'name'     => (string) $name,
				'label'    => wp_strip_all_tags( (string) ( $element['label'] ?? $name ) ),
				'category' => (string) ( $element['category'] ?? '' ),
				'nestable' => ! empty( $element['nestable'] ),
			);
		}
		set_transient( $key, $list, DAY_IN_SECONDS );
		return $list;
	}

	/**
	 * Compact control descriptions for one element.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function schema( string $name ): array {
		$result = array();
		foreach ( $this->controls( $name ) as $key => $control ) {
			$type  = (string) ( $control['type'] ?? 'text' );
			$entry = array(
				'key'   => (string) $key,
				'type'  => $type,
				'label' => wp_strip_all_tags( (string) ( $control['label'] ?? '' ) ),
				'tab'   => (string) ( $control['tab'] ?? 'content' ),
			);
			if ( ! empty( $control['group'] ) ) {
				$entry['group'] = (string) $control['group'];
			}
			if ( isset( self::KEY_HINTS[ $key ] ) ) {
				$entry['value'] = self::KEY_HINTS[ $key ];
			} elseif ( isset( self::VALUE_SHAPES[ $type ] ) ) {
				$entry['value'] = self::VALUE_SHAPES[ $type ];
			}
			if ( ! empty( $control['options'] ) && is_array( $control['options'] ) ) {
				$entry['options'] = array_slice( array_map( 'strval', array_keys( $control['options'] ) ), 0, 40 );
			}
			if ( isset( $control['default'] ) && is_scalar( $control['default'] ) ) {
				$entry['default'] = $control['default'];
			}
			if ( ! empty( $control['css'] ) ) {
				$entry['responsive'] = true;
			}
			$result[] = $entry;
		}
		return $result;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function controls( string $name ): array {
		if ( isset( $this->controls[ $name ] ) ) {
			return $this->controls[ $name ];
		}
		$controls = array();
		foreach ( (array) ( $this->element( $name )['controls'] ?? array() ) as $key => $control ) {
			if ( is_array( $control ) && ! in_array( $control['type'] ?? '', self::NON_VALUE_TYPES, true ) ) {
				$controls[ (string) $key ] = $control;
			}
		}
		$this->controls[ $name ] = $controls;
		return $controls;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function element( string $name ): array {
		if ( ! $this->has( $name ) ) {
			return array();
		}
		$element = \Bricks\Elements::get_element( array( 'name' => $name ) );
		return is_array( $element ) ? $element : array();
	}
}
