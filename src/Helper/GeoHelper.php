<?php

namespace CommonsBooking\Helper;

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
	 * @see NominatimGeoCodeService::getAddressData() for default values and more information
	 * @param string $addressString
	 *
	 * @return GeoLocation|null
	 */
	public static function getAddressData( $addressString ): ?GeoLocation {
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
