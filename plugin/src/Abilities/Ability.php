<?php
/**
 * Base class for every AC MCP for Bricks and Elementor ability.
 *
 * @package AcMcp
 */

namespace AcMcp\Abilities;

use AcMcp\Environment;
use AcMcp\Guides\Guides;
use AcMcp\Safety\Snapshots;
use AcMcp\Settings;
use AcMcp\Support\ChangeReport;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Describes one tool and adapts it to wp_register_ability().
 *
 * Subclasses declare their JSON Schema properties, a capability check and a
 * run() method. Input reaching run() has been validated by core against the
 * schema and has every declared default filled in.
 */
abstract class Ability {

	public const NAMESPACE = 'ac-mcp';

	public function __construct(
		protected Settings $settings,
		protected Environment $environment,
		protected Snapshots $snapshots
	) {}

	/** Short name, e.g. "list-pages". */
	abstract public function slug(): string;

	abstract public function label(): string;

	/** Written for an LLM: when to use it, what it returns, common pitfalls. */
	abstract protected function description(): string;

	/**
	 * JSON Schema properties of the input object.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	abstract protected function properties(): array;

	abstract public function is_readonly(): bool;

	/** Capability check for the current user. */
	abstract protected function authorize( array $input ): bool;

	/**
	 * Performs the work. Return a WP_Error built with Errors::make() on failure.
	 *
	 * @param array<string, mixed> $input Validated input with defaults applied.
	 * @return array<string, mixed>|WP_Error
	 */
	abstract protected function run( array $input );

	/**
	 * Required input keys.
	 *
	 * @return list<string>
	 */
	protected function required(): array {
		return array();
	}

	/**
	 * JSON Schema of a successful result. Empty means unchecked.
	 *
	 * @return array<string, mixed>
	 */
	protected function output_schema(): array {
		return array();
	}

	protected function is_destructive(): bool {
		return false;
	}

	/**
	 * Whether this call only previews. Falls back to the site's dry-run default.
	 *
	 * @param array<string, mixed> $input Tool input.
	 */
	protected function is_dry_run( array $input ): bool {
		return (bool) ( $input['dryRun'] ?? $this->settings->get( 'dry_run_default' ) );
	}

	/**
	 * Schema for the shared dryRun flag.
	 *
	 * @return array<string, string>
	 */
	protected static function dry_run_property(): array {
		return array(
			'type'        => 'boolean',
			'description' => 'Preview the change without saving. Defaults to the site setting (see site-info).',
		);
	}

	/** Tool group shown in settings: general, bricks, elementor or custom-code. */
	public function group(): string {
		return 'general';
	}

	/** How the MCP server exposes this ability: tool, resource or prompt. */
	public function mcp_type(): string {
		return 'tool';
	}

	/**
	 * Extra MCP metadata, e.g. a resource URI.
	 *
	 * @return array<string, mixed>
	 */
	protected function mcp_meta(): array {
		return array();
	}

	/** False hides the tool, e.g. when its builder is not active. */
	public function is_available(): bool {
		return true;
	}

	public function name(): string {
		return self::NAMESPACE . '/' . $this->slug();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function input_schema(): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => $this->properties(),
			'additionalProperties' => false,
			'default'              => array(),
		);
		if ( $this->required() ) {
			$schema['required'] = $this->required();
		}
		return $schema;
	}

	/**
	 * Permission callback passed to core.
	 *
	 * @param mixed $input Raw input.
	 */
	public function permission( $input = null ): bool {
		return $this->authorize( $this->with_defaults( $input ) );
	}

	/**
	 * Execute callback passed to core.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute( $input = null ) {
		$result = $this->run( $this->with_defaults( $input ) );
		if ( ! is_array( $result ) ) {
			return $result;
		}
		if ( ! $this->is_readonly() ) {
			$result += ChangeReport::for_result( $result );
		}
		// Lets the AI notice when the site owner edits the guidelines mid-session.
		$result['guidelinesVersion'] = ( new Guides( $this->settings ) )->version();
		return $result;
	}

	/**
	 * Arguments for wp_register_ability().
	 *
	 * @return array<string, mixed>
	 */
	public function to_args(): array {
		$args = array(
			'label'               => $this->label(),
			'description'         => $this->description(),
			'category'            => Registry::CATEGORY,
			'input_schema'        => $this->input_schema(),
			'execute_callback'    => array( $this, 'execute' ),
			'permission_callback' => array( $this, 'permission' ),
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => $this->is_readonly(),
					'destructive' => $this->is_destructive(),
					'idempotent'  => $this->is_readonly(),
				),
				'show_in_rest' => false,
				// Only exposed through AC MCP for Bricks and Elementor's own server, never the adapter's default server.
				'mcp'          => array(
					'public' => false,
					'type'   => $this->mcp_type(),
				) + $this->mcp_meta(),
			),
		);
		if ( $this->output_schema() ) {
			$args['output_schema'] = $this->output_schema();
		}
		return $args;
	}

	/**
	 * Fills declared defaults for keys the caller left out.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	protected function with_defaults( $input ): array {
		$input = is_array( $input ) ? $input : (array) ( is_object( $input ) ? get_object_vars( $input ) : array() );
		foreach ( $this->properties() as $key => $property ) {
			if ( ! array_key_exists( $key, $input ) && array_key_exists( 'default', $property ) ) {
				$input[ $key ] = $property['default'];
			}
		}
		return $input;
	}
}
