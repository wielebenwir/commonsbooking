<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Plugin;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase {

	public function testGetCacheIdLimitsBacktraceDepth() {
		if ( ! function_exists( 'uopz_set_return' ) ) {
			$this->markTestSkipped( 'The uopz extension is required to inspect the debug_backtrace call.' );
		}

		$backtraceOptions = null;
		$backtraceLimit   = null;
		$backtrace        = [
			[],
			[],
			[
				'class'    => self::class,
				'function' => __FUNCTION__,
				'args'     => [],
			],
		];

		uopz_set_return(
			'debug_backtrace',
			function (
				$options = DEBUG_BACKTRACE_PROVIDE_OBJECT,
				$limit = 0
			) use (
				&$backtraceOptions,
				&$backtraceLimit,
				$backtrace
			) {
				$backtraceOptions = $options;
				$backtraceLimit   = $limit;

				return $backtrace;
			},
			true
		);

		try {
			Plugin::getCacheId();
		} finally {
			uopz_unset_return( 'debug_backtrace' );
		}

		$this->assertSame( 0, $backtraceOptions );
		$this->assertSame( 3, $backtraceLimit );
	}

	public function testGetAdapters() {
		// check, if the adapters exist
		$adapters = Plugin::getAdapters();
		foreach ( $adapters as $adapter ) {
			$this->assertInstanceOf( \Closure::class, $adapter['factory'] );
		}

		// check, if the labels are correctly returned
		$adapterLabels = Plugin::getAdapters( true );
		$this->assertIsArray( $adapterLabels );
		foreach ( $adapterLabels as $labelKey => $labelText ) {
			$this->assertIsString( $labelKey );
			$this->assertIsString( $labelText );
		}
	}
}
