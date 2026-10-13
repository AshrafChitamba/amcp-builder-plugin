<?php
/**
 * PHPUnit bootstrap: Brain Monkey unit tests, no WordPress install needed.
 *
 * @package McpBuilder
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MCP_BUILDER_VERSION', '0.0.0-test' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs.php';
