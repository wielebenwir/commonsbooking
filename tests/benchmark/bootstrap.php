<?php
/**
 * PHPBench bootstrap file.
 *
 * @package Commonsbooking
 */

// Reflection subprocesses only need the autoloader. WordPress is already
// running in the local executor process started by phpbench.php.
if ( getenv( 'COMMONSBOOKING_BENCHMARK_BOOTSTRAPPED' ) === '1' ) {
	require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
	return;
}

// disable WP_DEBUG so we can run cached benchmarks
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

// Silence all output during reflection
ob_start();

// Suppress deprecations for PHPBench discovery
error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED );

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once "{$_tests_dir}/includes/functions.php";

function _manually_load_plugin() {
	require dirname( __DIR__, 2 ) . '/commonsbooking.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Composer autoload
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// allow redefining of WP_DEBUG
set_error_handler(
	static function ( int $severity, string $message ): bool {
		return $severity === E_WARNING && str_contains( $message, 'WP_DEBUG already defined' );
	}
);

// Bootstrap WordPress
require_once "{$_tests_dir}/includes/bootstrap.php";

restore_error_handler();

// Discard all output
ob_end_clean();
