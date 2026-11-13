<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Tests\BaseTestCase;
use CommonsBooking\Helper\GeoLocation;
use PHPUnit\Framework\TestCase;

/**
 * Tests wrapper impl for nominatim and provides mocking code to prevent real service calls
 */
class GeoHelperTest extends BaseTestCase {

	/**
	 * Mocks a location
	 */
	public static function mockedLocation(): GeoLocation {
		return new GeoLocation( 52.4863573, 13.4247667 );
	}

	/**
	 * This can be used to get mocked locations from nominatim
	 *
	 * @param TestCase $case
	 *
	 * @return void
	 */
	public static function setUpGeoHelperMock( TestCase $case ): void {

		$sut = $case->createStub( GeoCodeService::class );
		$sut->method( 'getAddressData' )
					->willReturn( self::mockedLocation() );
		GeoHelper::setGeoCodeServiceInstance( $sut );
	}

	public function testThatGeoCoding_worksOffline() {
		$address = GeoHelper::getAddressData( 'Karl-Marx-Straße 1, 12043 Berlin' );
		$this->assertThatKarlMarxLocationIsProperlyGeoCoded( $address );
	}

	private function assertThatKarlMarxLocationIsProperlyGeoCoded( GeoLocation $address ): void {
		$this->assertStringStartsWith( '52.4863', '' . $address->getLatitude() );
		$this->assertStringStartsWith( '13.424', '' . $address->getLongitude() );
		// GeoJSON order: lon, lat
		$this->assertSame( [ 13.4247667, 52.4863573 ], $address->toArray() );
	}
}
