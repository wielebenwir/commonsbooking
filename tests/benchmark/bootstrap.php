<?php
/**
 * PHPBench bootstrap file.
 *
 * @package Commonsbooking
 */

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

// Bootstrap WordPress
require_once "{$_tests_dir}/includes/bootstrap.php";

// Discard all output
ob_end_clean();
