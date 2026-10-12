<?php
/**
 * Minimal stand-ins for WordPress classes used by unit tests.
 *
 * @package McpBuilder
 */

// phpcs:disable

class WP_Error {
	public array $errors = array();
	public array $error_data = array();

	public function __construct( string $code = '', string $message = '', $data = '' ) {
		if ( '' !== $code ) {
			$this->errors[ $code ][] = $message;
			$this->error_data[ $code ] = $data;
		}
	}

	public function get_error_code(): string {
		return (string) array_key_first( $this->errors );
	}

	public function get_error_message(): string {
		return $this->errors[ $this->get_error_code() ][0] ?? '';
	}

	public function get_error_data() {
		return $this->error_data[ $this->get_error_code() ] ?? null;
	}
}

class WP_Post_Type {
	public string $name;
	public bool $show_ui = true;
	public object $cap;

	public function __construct( string $name ) {
		$this->name = $name;
		$this->cap  = (object) array(
			'create_posts'  => 'edit_' . $name . 's',
			'publish_posts' => 'publish_' . $name . 's',
		);
	}
}
