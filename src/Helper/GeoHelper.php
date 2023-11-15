<?php

namespace CommonsBooking\Helper;

use Geocoder\Location;

/**
 * Wrapper for calling the geoCoder service
 */
class GeoHelper {

	/**
	 * @param string $addressString
	 *
	 * @return Location|null
	 */
	public static function getAddressData( $addressString ): ?Location {
		return GeoCoderServiceProxy::getInstance()->getAddressData( $addressString );
	}
}
