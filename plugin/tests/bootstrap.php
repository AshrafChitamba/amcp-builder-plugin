<?php
/**
 * PHPUnit bootstrap: Brain Monkey unit tests, no WordPress install needed.
 *
 * @package McpBuilder
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MCP_BUILDER_VERSION', '0.0.0-test' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs.php';
