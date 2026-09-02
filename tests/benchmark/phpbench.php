<?php

/**
 * Boots WordPress once before running PHPBench's local executor.
 *
 * @package Commonsbooking
 */

require_once __DIR__ . '/bootstrap.php';

putenv( 'COMMONSBOOKING_BENCHMARK_BOOTSTRAPPED=1' );

require dirname( __DIR__, 2 ) . '/vendor/phpbench/phpbench/bin/phpbench.php';
