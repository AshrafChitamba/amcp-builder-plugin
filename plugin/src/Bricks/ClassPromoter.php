<?php
/**
 * Turns plain CSS classes into Bricks global classes.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Bricks shows plain classes (_cssClasses) only under Style → CSS, where
 * nobody looks; the class selector next to the element ID lists global
 * classes (_cssGlobalClasses) only. So every plain class an AI sets is
 * attached as a global class instead, reusing a class with the same name or
 * creating an empty one. New classes are collected here and only saved after
 * the page itself has been saved.
 */
final class ClassPromoter {

	/** @var array<string, string> Class name => ID, for every class known so far. */
	private array $ids = array();

	/** @var list<array<string, mixed>> Classes to create after saving. */
	private array $pending = array();

	private Ids $id_generator;

	/**
	 * @param list<array<string, mixed>>|null $existing Existing global classes; read from the site when null.
	 */
	public function __construct( ?array $existing = null ) {
		$existing = $existing ?? GlobalClasses::all();
		foreach ( $existing as $class ) {
			if ( isset( $class['name'], $class['id'] ) ) {
				$this->ids[ (string) $class['name'] ] = (string) $class['id'];
			}
		}
		$this->id_generator = new Ids( array_values( $this->ids ) );
	}

	/**
	 * Promotes classes in nested element specs (name, settings, children).
	 *
	 * @param mixed $specs Specs from the tool input.
	 * @return mixed
	 */
	public function specs( $specs ) {
		if ( ! is_array( $specs ) ) {
			return $specs;
		}
		foreach ( $specs as $index => $spec ) {
			if ( ! is_array( $spec ) ) {
				continue;
			}
			if ( isset( $spec['settings'] ) && is_array( $spec['settings'] ) ) {
				$spec['settings'] = $this->settings( $spec['settings'] );
			}
			if ( isset( $spec['children'] ) ) {
				$spec['children'] = $this->specs( $spec['children'] );
			}
			$specs[ $index ] = $spec;
		}
		return $specs;
	}

	/**
	 * Moves "_cssClasses" names into "_cssGlobalClasses" IDs.
	 *
	 * @param array<string, mixed> $settings Element settings.
	 * @return array<string, mixed>
	 */
	public function settings( array $settings ): array {
		if ( ! array_key_exists( '_cssClasses', $settings ) || ! is_string( $settings['_cssClasses'] ) ) {
			return $settings;
		}

		$names = preg_split( '/\s+/', trim( $settings['_cssClasses'] ), -1, PREG_SPLIT_NO_EMPTY );
		unset( $settings['_cssClasses'] );
		if ( ! $names ) {
			return $settings;
		}

		$global = is_array( $settings['_cssGlobalClasses'] ?? null ) ? $settings['_cssGlobalClasses'] : array();
		foreach ( $names as $name ) {
			$name = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $name );
			if ( '' !== $name ) {
				$global[] = $this->id_for( $name );
			}
		}
		$settings['_cssGlobalClasses'] = array_values( array_unique( $global ) );
		return $settings;
	}

	/**
	 * Global classes that must be created once the page is saved.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function pending(): array {
		return $this->pending;
	}

	/**
	 * Saves the pending classes. Call only after the page was saved.
	 */
	public function save(): void {
		foreach ( $this->pending as $class ) {
			GlobalClasses::save( $class );
		}
		$this->pending = array();
	}

	private function id_for( string $name ): string {
		if ( isset( $this->ids[ $name ] ) ) {
			return $this->ids[ $name ];
		}
		$id                 = $this->id_generator->next();
		$this->ids[ $name ] = $id;
		$this->pending[]    = array(
			'id'       => $id,
			'name'     => $name,
			'settings' => array(),
		);
		return $id;
	}
}
