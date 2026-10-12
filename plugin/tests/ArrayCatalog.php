<?php
/**
 * In-memory element catalog for tests.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Tests;

use McpBuilder\Bricks\Catalog;

final class ArrayCatalog implements Catalog {

	/**
	 * @param array<string, array{nestable: bool, keys: list<string>}> $elements
	 */
	public function __construct( private array $elements ) {}

	public static function default(): self {
		$style = array( '_padding', '_margin', '_typography', '_background', '_display', '_gridTemplateColumns', '_gridGap', '_width' );
		return new self(
			array(
				'section'    => array( 'nestable' => true, 'keys' => array_merge( $style, array( 'tag' ) ) ),
				'container'  => array( 'nestable' => true, 'keys' => array_merge( $style, array( 'tag' ) ) ),
				'block'      => array( 'nestable' => true, 'keys' => $style ),
				'heading'    => array( 'nestable' => false, 'keys' => array_merge( $style, array( 'text', 'tag' ) ) ),
				'text-basic' => array( 'nestable' => false, 'keys' => array_merge( $style, array( 'text', 'tag' ) ) ),
				'image'      => array( 'nestable' => false, 'keys' => array_merge( $style, array( 'image', 'altText' ) ) ),
			)
		);
	}

	public function has( string $name ): bool {
		return isset( $this->elements[ $name ] );
	}

	public function nestable( string $name ): bool {
		return $this->elements[ $name ]['nestable'] ?? false;
	}

	public function setting_keys( string $name ): array {
		return $this->elements[ $name ]['keys'] ?? array();
	}
}
