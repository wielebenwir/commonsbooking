<?php

namespace CommonsBooking\Helper;

use Geocoder\Location;

/**
 * Wrapper for calling the geoCoder service.
 * Defaults to implementation of {@see NominatimGeoCodeService}.
 */
class GeoHelper {

	/**
	 * @var GeoCodeService Singleton instance
	 */
	private static GeoCodeService $geoCodeService;

	/**
	 * @param string $addressString
	 *
	 * @return Location|null
	 */
	public static function getAddressData( $addressString ): ?Location {
		if ( ! isset( self::$geoCodeService ) ) {
			self::resetGeoCoder();
		}
		return self::$geoCodeService->getAddressData( $addressString );
	}

	/**
	 * Configure the service implementation in use
	 *
	 * @param GeoCodeService $instance
	 *
	 * @return void
	 */
	public static function setGeoCodeServiceInstance( GeoCodeService $instance ): void {
		self::$geoCodeService = $instance;
	}

	public static function resetGeoCoder(): void {
		self::setGeoCodeServiceInstance( new NominatimGeoCodeService() );
	}
}
