<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Tests\Helper\GeoHelperTest;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase {

	protected function setUp(): void {

		// Default case: Tests should work offline
		GeoHelperTest::setUpGeoHelperMock( $this );
	}
}