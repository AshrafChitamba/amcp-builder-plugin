<?php
/**
 * REST routes used by the settings screen.
 *
 * @package McpBuilder
 */

namespace McpBuilder\Rest;

use McpBuilder\Abilities\Ability;
use McpBuilder\Abilities\Registry;
use McpBuilder\Environment;
use McpBuilder\Mcp\Server;
use McpBuilder\Requirements;
use McpBuilder\Safety\AuditLog;
use McpBuilder\Safety\Governance;
use McpBuilder\Safety\Snapshots;
use McpBuilder\Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only routes under /wp-json/mcp-builder/v1.
 */
final class AdminController {

	public const NAMESPACE = 'mcp-builder/v1';

	public function __construct(
		private Settings $settings,
		private Environment $environment,
		private Requirements $requirements,
		private Registry $abilities,
		private AuditLog $audit,
		private Snapshots $snapshots
	) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn() => rest_ensure_response( $this->settings->all() ),
					'permission_callback' => array( $this, 'is_admin' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'is_admin' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'status' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/abilities',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'abilities' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'audit' ),
				'permission_callback' => array( $this, 'is_admin' ),
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 100,
						'default' => 25,
					),
					'ability'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'outcome'  => array(
						'type' => 'string',
						'enum' => array_merge( array( '' ), AuditLog::OUTCOMES ),
					),
					'post_id'  => array(
						'type'    => 'integer',
						'minimum' => 0,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/snapshots',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_snapshots' ),
				'permission_callback' => array( $this, 'is_admin' ),
				'args'                => array(
					'post_id' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/snapshots/(?P<id>\\d+)/restore',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore_snapshot' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth-header-test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'auth_header_test' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);
	}

	public function is_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$patch = $request->get_json_params();
		return rest_ensure_response( $this->settings->update( is_array( $patch ) ? $patch : array() ) );
	}

	public function status(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'checks'      => $this->requirements->all_checks(),
				'environment' => $this->environment->to_array(),
				'canRun'      => $this->requirements->can_run(),
				'endpoint'    => Server::url(),
				'tools'       => array_map(
					static fn( Ability $ability ): array => array(
						'name'      => $ability->name(),
						'label'     => $ability->label(),
						'group'     => $ability->group(),
						'readonly'  => $ability->is_readonly(),
						'available' => $ability->is_available(),
					),
					$this->abilities->all()
				),
			)
		);
	}

	/**
	 * Every ability MCP Builder governs: its own and official builder ones.
	 */
	public function abilities(): WP_REST_Response {
		$disabled = $this->settings->get( 'disabled_tools' );
		$items    = array();

		foreach ( function_exists( 'wp_get_abilities' ) ? wp_get_abilities() : array() as $ability ) {
			$name = $ability->get_name();
			if ( ! Governance::is_managed( $name ) ) {
				continue;
			}
			$own = $this->abilities->find( $name );
			if ( $own ) {
				$group = $own->group();
			} elseif ( in_array( $name, Governance::CUSTOM_CODE_ABILITIES, true ) ) {
				$group = 'custom-code';
			} else {
				$group = 'official-' . explode( '/', $name )[0];
			}

			$items[] = array(
				'name'        => $name,
				'label'       => $ability->get_label(),
				'description' => wp_html_excerpt( explode( "\n", $ability->get_description() )[0], 220, '…' ),
				'group'       => $group,
				'readonly'    => Governance::is_readonly( $ability ),
				'enabled'     => ! in_array( $name, $disabled, true ),
			);
		}

		return rest_ensure_response( $items );
	}

	public function audit( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response(
			$this->audit->query(
				array(
					'ability' => $request['ability'],
					'outcome' => $request['outcome'],
					'post_id' => $request['post_id'],
				),
				(int) $request['page'],
				(int) $request['per_page']
			)
		);
	}

	public function list_snapshots( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->snapshots->list( (int) $request['post_id'], 50 ) );
	}

	/**
	 * @return WP_REST_Response|\WP_Error
	 */
	public function restore_snapshot( WP_REST_Request $request ) {
		$result = $this->snapshots->restore( (int) $request['id'], 'admin/restore-snapshot' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$this->audit->record(
			array(
				'ability'     => 'admin/restore-snapshot',
				'outcome'     => 'ok',
				'snapshot_id' => $result['undoSnapshotId'],
				'message'     => sprintf( 'Restored snapshot #%d from the settings screen.', (int) $request['id'] ),
			)
		);
		return rest_ensure_response( $result );
	}

	/**
	 * Sends a loopback request with deliberately wrong Basic credentials.
	 *
	 * If the Authorization header reaches WordPress, core rejects the unknown
	 * user (invalid_username). If the server strips the header, WordPress sees
	 * an anonymous request instead (rest_not_logged_in), which also breaks
	 * Application Passwords for AI clients.
	 */
	public function auth_header_test(): WP_REST_Response {
		$response = wp_remote_get(
			rest_url( 'wp/v2/users/me' ),
			array(
				'timeout'   => 10,
				// Same convention core uses for loopback requests to local self-signed certificates.
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
				'headers'   => array(
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth encoding.
					'Authorization' => 'Basic ' . base64_encode( 'mcp-builder-header-test-' . wp_generate_password( 8, false ) . ':invalid' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'unknown',
					'message' => sprintf(
						/* translators: %s: error message. */
						__( 'The site could not reach its own REST API (%s). This is common on local sites and does not always mean AI clients will fail.', 'mcp-builder' ),
						$response->get_error_message()
					),
				)
			);
		}

		$code = json_decode( (string) wp_remote_retrieve_body( $response ), true )['code'] ?? '';

		return rest_ensure_response(
			match ( $code ) {
				'invalid_username', 'incorrect_password', 'application_passwords_disabled' => array(
					'status'  => 'ok',
					'message' => __( 'The Authorization header reaches WordPress.', 'mcp-builder' ),
				),
				'rest_not_logged_in' => array(
					'status'  => 'error',
					'message' => __( 'The web server strips the Authorization header, so Application Passwords cannot work. See Troubleshooting → "Authorization header stripped".', 'mcp-builder' ),
				),
				default => array(
					'status'  => 'unknown',
					/* translators: %s: REST error code. */
					'message' => sprintf( __( 'Unexpected response (%s). A security plugin or firewall may be intercepting REST requests.', 'mcp-builder' ), $code ? $code : wp_remote_retrieve_response_code( $response ) ),
				),
			}
		);
	}
}
