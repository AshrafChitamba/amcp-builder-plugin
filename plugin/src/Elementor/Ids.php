<?php
/**
 * Elementor element ID generator.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Generates 7-character lowercase hex IDs (the format the Elementor editor
 * uses, see elementorCommon.helpers.getUniqueId) that are unique within one document.
 */
final class Ids {

	private const ALPHABET = '0123456789abcdef';

	/** @var array<string, true> */
	private array $taken = array();

	/**
	 * @param list<string> $taken IDs already used in the document.
	 */
	public function __construct( array $taken = array() ) {
		$this->taken = array_fill_keys( $taken, true );
	}

	public function next(): string {
		do {
			$id = '';
			for ( $i = 0; $i < 7; $i++ ) {
				$id .= self::ALPHABET[ random_int( 0, 15 ) ];
			}
		} while ( isset( $this->taken[ $id ] ) || ctype_digit( $id ) );

		$this->taken[ $id ] = true;
		return $id;
	}
}
