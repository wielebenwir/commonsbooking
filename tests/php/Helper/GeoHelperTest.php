<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\GeoCoderServiceProxy;
use CommonsBooking\Helper\GeoHelper;
use Geocoder\Location;
use Geocoder\Model\AddressBuilder;
use PHPUnit\Framework\TestCase;

class GeoHelperTest extends TestCase
{

	/**
	 * Mocks a location
	 *
	 * @return Location|null
	 */
	private static function mockedLocation() : ?Location {
		$location = new AddressBuilder("Mock");
		$location->setStreetName("Karl-Marx-Straße")
		         ->setStreetNumber("1")
		         ->setPostalCode("12043")
		         ->setLocality("Berlin")
		         ->setCountry("Germany")
		         ->setCoordinates(52.4863922, 13.424689);

		return $location->build();
	}

	/**
	 * This can be used to get mocked locations from nominatim
	 *
	 * @param TestCase $case
	 *
	 * @return void
	 */
	public static function setupGeoHelperMock( TestCase $case ) : void {

		$sut = $case->createStub(GeoCoderServiceProxy::class);
		$sut->method('getAddressData')
		           ->willReturn(self::mockedLocation());
		GeoCoderServiceProxy::setInstance($sut);
	}

	public function setup() : void {
		self::setupGeoHelperMock($this);
	}

    public function testGetAddressData()
    {
		$address = GeoHelper::getAddressData('Karl-Marx-Straße 1, 12043 Berlin');

		$this->assertEquals('Karl-Marx-Straße', $address->getStreetName());
		$this->assertEquals('1', $address->getStreetNumber());
		$this->assertEquals('12043', $address->getPostalCode());
		$this->assertEquals('Berlin', $address->getLocality());
		$this->assertEquals('Germany', $address->getCountry());
		$this->assertEquals('52.4863922', $address->getCoordinates()->getLatitude());
		$this->assertEquals('13.424689', $address->getCoordinates()->getLongitude());

    }
}
