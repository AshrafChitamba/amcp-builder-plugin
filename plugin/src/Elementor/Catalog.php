<?php
/**
 * Element catalog contract.
 *
 * @package AcMcp
 */

namespace AcMcp\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * What the validator needs to know about Elementor element and widget types.
 *
 * An element type is identified by its elType ("container", "section",
 * "column", "widget") and, for widgets, its widgetType ("heading", "image"…).
 */
interface Catalog {

	public function exists( string $el_type, ?string $widget_type ): bool;

	/** Whether the element can contain child elements. */
	public function accepts_children( string $el_type, ?string $widget_type ): bool;

	/**
	 * Valid settings keys, including responsive variants such as "padding_mobile".
	 *
	 * @return list<string>
	 */
	public function setting_keys( string $el_type, ?string $widget_type ): array;
}
