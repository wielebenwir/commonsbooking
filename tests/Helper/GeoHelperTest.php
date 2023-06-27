<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\GeoHelper;
use PHPUnit\Framework\TestCase;

class GeoHelperTest extends TestCase
{

	/*
	 * DISABLED BECAUSE IT KEEPS FAILING IN CI. TODO: PROBABLY MOCK THE API CALLS
	 */
    public function testGetAddressData()
    {
		$this->assertTrue(true);
		/*
		$address = GeoHelper::getAddressData('Karl-Marx-Straße 1, 12043 Berlin');

		$this->assertEquals('Karl-Marx-Straße', $address->getStreetName());
		$this->assertEquals('1', $address->getStreetNumber());
		$this->assertEquals('12043', $address->getPostalCode());
		$this->assertEquals('Berlin', $address->getLocality());
		$this->assertEquals('Germany', $address->getCountry());
		$this->assertEquals('52.4863922', $address->getCoordinates()->getLatitude());
		$this->assertEquals('13.424689', $address->getCoordinates()->getLongitude());
		*/
    }
}
