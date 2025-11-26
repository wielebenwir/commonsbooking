<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Composer_Dependencies\Geocoder\Location;

interface GeoCodeService {

	/**
	 * Returns a geocoded location object from a given address string
	 *
	 * @param string $addressString
	 *
	 * @return Location|null
	 */
	public function getAddressData( string $addressString ): ?Location;
}
