<?php
/**
 * Minimal stand-ins for WordPress classes used by unit tests.
 *
 * @package AcMcp
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

class WP_Ability {
	public function __construct( private string $name, private array $meta = array() ) {}

	public function get_name(): string {
		return $this->name;
	}

	public function get_meta(): array {
		return $this->meta;
	}
}

class WP_Post {
	public int $ID;
	public string $post_title;
	public string $post_status;
	public string $post_type;
}

class WP_Query {
	/** @var list<WP_Post> Posts returned by the query in unit tests. */
	public static array $test_posts = array();
	/** @var list<WP_Post> */
	public array $posts;

	public function __construct( array $args = array() ) {
		$this->posts = self::$test_posts;
	}
}
