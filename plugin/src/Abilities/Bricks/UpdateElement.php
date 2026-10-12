<?php
/**
 * The bricks-update-element tool.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Abilities\Bricks;

use McpBuilder\Bricks\Tree;
use McpBuilder\Bricks\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Changes the settings or label of one element.
 */
final class UpdateElement extends BricksWriteAbility {

	public function slug(): string {
		return 'bricks-update-element';
	}

	public function label(): string {
		return __( 'Bricks: update element', 'mcp-builder' );
	}

	protected function description(): string {
		return 'Changes the settings (and optionally the label) of one element on a Bricks page. '
			. 'mode "merge" (default) changes only the keys you pass; a key set to null is removed. mode "replace" replaces all settings. '
			. 'In "_cssCustom", %root% is replaced with the element selector (#brxe-ID) on save. '
			. 'Get the element ID from bricks-get-page and valid keys from bricks-get-element-schema. A snapshot is taken before saving.';
	}

	protected function change_properties(): array {
		return array(
			'elementId' => array( 'type' => 'string' ),
			'settings'  => array(
				'type'    => 'object',
				'default' => array(),
			),
			'mode'      => array(
				'type'    => 'string',
				'enum'    => array( 'merge', 'replace' ),
				'default' => 'merge',
			),
			'label'     => array(
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => 'New label shown in the Bricks structure panel. Empty string removes it.',
			),
		);
	}

	protected function change_required(): array {
		return array( 'elementId' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$element  = $tree->get( (string) $input['elementId'] );
		$settings = (array) $input['settings'];

		$validator->settings( $element['name'], array_filter( $settings, static fn( $value ): bool => null !== $value ) );
		$tree->update( $element['id'], $settings, $input['mode'], $input['label'] ?? null );

		return array( 'elementId' => $element['id'] );
	}
}
