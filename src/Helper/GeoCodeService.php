<?php

namespace CommonsBooking\Helper;

interface GeoCodeService {

	/**
	 * Returns a geocoded location object from a given address string
	 *
	 * @param string $addressString
	 *
	 * @return GeoLocation|null
	 */
	public function getAddressData( string $addressString ): ?GeoLocation;
}
