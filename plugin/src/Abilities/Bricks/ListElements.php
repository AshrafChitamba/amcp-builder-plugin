<?php
/**
 * The bricks-list-elements tool.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Abilities\Bricks;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the element types registered on this site.
 */
final class ListElements extends BricksAbility {

	public function slug(): string {
		return 'bricks-list-elements';
	}

	public function label(): string {
		return __( 'Bricks: list elements', 'amcp-builder' );
	}

	protected function description(): string {
		return 'Lists the Bricks element types available on this site (including custom elements), with label, category and whether they can contain children (nestable). '
			. 'Use the name values in bricks-add-elements. Layout: section > container > block/div. Common content elements: heading, text-basic, text, button, image, icon, icon-box, list. '
			. 'Then call bricks-get-element-schema for the settings of each element you plan to use.';
	}

	protected function properties(): array {
		return array(
			'category' => array(
				'type'        => 'string',
				'maxLength'   => 50,
				'description' => 'Only this category, e.g. "layout", "basic", "general", "media".',
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
		$elements = $this->catalog()->all();
		$search   = strtolower( (string) ( $input['search'] ?? '' ) );

		$filtered = array_values(
			array_filter(
				$elements,
				static fn( array $element ): bool => ( empty( $input['category'] ) || $element['category'] === $input['category'] )
					&& ( '' === $search || str_contains( strtolower( $element['name'] . ' ' . $element['label'] ), $search ) )
			)
		);

		return array(
			'elements'   => $filtered,
			'categories' => array_values( array_unique( array_column( $elements, 'category' ) ) ),
		);
	}
}
