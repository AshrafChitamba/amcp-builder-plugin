<?php
/**
 * Element catalog contract.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * What the validator needs to know about element types.
 */
interface Catalog {

	public function has( string $name ): bool;

	/** Whether the element can contain child elements. */
	public function nestable( string $name ): bool;

	/**
	 * Valid settings keys (without breakpoint or pseudo-class suffixes).
	 *
	 * @return list<string>
	 */
	public function setting_keys( string $name ): array;
}
