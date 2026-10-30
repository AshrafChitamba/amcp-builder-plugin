<?php
/**
 * The elementor-list-widgets tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the widget types registered on this site.
 */
final class ListWidgets extends ElementorAbility {

	public function slug(): string {
		return 'elementor-list-widgets';
	}

	public function label(): string {
		return __( 'Elementor: list widgets', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Lists the classic Elementor widgets available on this site (including Pro and add-on widgets), with title, categories and whether they need Pro. '
			. 'Use the name values as widgetType in elementor-add-elements. Layout uses elType "container" (see layoutTypes). '
			. 'Common widgets: heading, text-editor, image, button, icon, icon-box, image-box, icon-list, divider, spacer, star-rating, testimonial, form (Pro). '
			. 'Then call elementor-get-widget-schema for the settings of each widget you plan to use.';
	}

	protected function properties(): array {
		return array(
			'category' => array(
				'type'        => 'string',
				'maxLength'   => 50,
				'description' => 'Only this category, e.g. "basic", "general", "pro-elements".',
			),
			'search'   => array(
				'type'      => 'string',
				'maxLength' => 50,
			),
		);
	}

	public function is_readonly(): bool {
		return true;
	}

	protected function run( array $input ): array {
		$widgets = $this->catalog()->widgets();
		$search  = strtolower( (string) ( $input['search'] ?? '' ) );

		$filtered = array_values(
			array_filter(
				$widgets,
				static fn( array $widget ): bool => ( empty( $input['category'] ) || in_array( $input['category'], $widget['categories'], true ) )
					&& ( '' === $search || str_contains( strtolower( $widget['name'] . ' ' . $widget['title'] ), $search ) )
			)
		);

		return array(
			'widgets'     => $filtered,
			'categories'  => array_values( array_unique( array_merge( ...array_column( $widgets, 'categories' ) ) ) ),
			'layoutTypes' => $this->catalog()->layout_types(),
		);
	}
}
