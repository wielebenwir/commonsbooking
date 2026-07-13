<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Plugin;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase {

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
