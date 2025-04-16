<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Tests\BaseTestCase;
use Geocoder\Location;
use Geocoder\Model\AddressBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests wrapper impl for nominatim and provides mocking code to prevent real service calls
 */
class GeoHelperTest extends BaseTestCase {

	/**
	 * Mocks a location
	 *
	 * @return Location|null
	 */
	private static function mockedLocation(): ?Location {
		$location = new AddressBuilder( 'Mock' );
		$location->setStreetName( 'Karl-Marx-Straße' )
				->setStreetNumber( '1' )
				->setPostalCode( '12043' )
				->setLocality( 'Berlin' )
				->setCountry( 'Germany' )
				->setCoordinates( 52.4863573, 13.4247667 );

		return $location->build();
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

	public function testThatGeoCoding_worksOnline() {
		GeoHelper::resetGeoCoder();

		$address = GeoHelper::getAddressData( 'Karl-Marx-Straße 1, 12043 Berlin' );
		$this->assertThatKarlMarxLocationIsProperlyGeoCoded( $address );
	}
	private function assertThatKarlMarxLocationIsProperlyGeoCoded( Location $address ): void {
		$this->assertEquals( 'Karl-Marx-Straße', $address->getStreetName() );
		$this->assertEquals( '1', $address->getStreetNumber() );
		$this->assertEquals( '12043', $address->getPostalCode() );
		$this->assertEquals( 'Berlin', $address->getLocality() );
		$this->assertEquals( 'Germany', $address->getCountry() );
		// This won't check exact coords on purpose, because sometimes there are different results
		$this->assertStringStartsWith( '52.4863', '' . $address->getCoordinates()->getLatitude() );
		$this->assertStringStartsWith( '13.424', '' . $address->getCoordinates()->getLongitude() );
	}
}
