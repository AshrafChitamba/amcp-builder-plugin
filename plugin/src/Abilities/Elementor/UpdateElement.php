<?php
/**
 * The elementor-update-element tool.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities\Elementor;

use AcMcp\Elementor\Tree;
use AcMcp\Elementor\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Changes the settings of one element.
 */
final class UpdateElement extends ElementorWriteAbility {

	public function slug(): string {
		return 'elementor-update-element';
	}

	public function label(): string {
		return __( 'Elementor: update element', 'ac-mcp-for-bricks-elementor' );
	}

	protected function description(): string {
		return 'Changes the settings of one element on an Elementor page. '
			. 'mode "merge" (default) changes only the keys you pass; a key set to null is removed (inside __globals__ too). mode "replace" replaces all settings. '
			. 'Get the element ID from elementor-get-page and valid keys from elementor-get-widget-schema. Atomic (v4) elements cannot be changed. A snapshot is taken before saving.';
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
		);
	}

	protected function change_required(): array {
		return array( 'elementId', 'settings' );
	}

	protected function mutate( Tree $tree, array $input, Validator $validator ): array {
		$element  = $tree->get( (string) $input['elementId'] );
		$settings = (array) $input['settings'];

		$validator->editable( $element );
		$validator->settings(
			(string) $element['elType'],
			$element['widgetType'] ?? null,
			array_filter( $settings, static fn( $value ): bool => null !== $value )
		);
		$tree->update( $element['id'], $settings, $input['mode'] );

		return array( 'elementId' => $element['id'] );
	}
}
