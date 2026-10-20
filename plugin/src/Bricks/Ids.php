<?php
/**
 * Bricks element ID generator.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Generates 6-character lowercase alphanumeric IDs (the format Bricks uses,
 * see Helpers::generate_random_id) that are unique within one page.
 */
final class Ids {

	private const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';

	/** @var array<string, true> */
	private array $taken = array();

	/**
	 * @param list<string> $taken IDs already used on the page.
	 */
	public function __construct( array $taken = array() ) {
		$this->taken = array_fill_keys( $taken, true );
	}

	public function next(): string {
		do {
			// Start with a letter so the ID is also a valid CSS identifier fragment.
			$id = self::ALPHABET[ random_int( 0, 25 ) ];
			for ( $i = 1; $i < 6; $i++ ) {
				$id .= self::ALPHABET[ random_int( 0, 35 ) ];
			}
		} while ( isset( $this->taken[ $id ] ) );

		$this->taken[ $id ] = true;
		return $id;
	}
}
