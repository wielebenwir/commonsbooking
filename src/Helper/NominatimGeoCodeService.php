<?php

namespace CommonsBooking\Helper;

use Geocoder\Exception\Exception;
use Geocoder\Location;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Http\Client\Curl\Client;

/**
 * Implementation of geocoding web service calls.
 * Helps to properly mock/unit-test 3rd-party components.
 */
class NominatimGeoCodeService implements GeoCodeService {

	/**
	 * NOTE: This uses the english locale since we only use the coordinates of the returned address objects
	 *
	 * @param $addressString
	 *
	 * @return ?Location
	 * @throws \Exception
	 */
	public function getAddressData( $addressString ): ?Location {

		// The Nomination service requires the curl extension to be installed
		if ( ! function_exists( 'curl_version' ) ) {
			throw new \Exception( 'Could not get address data because of missing curl extension.' );
		}

		$defaultUserAgent = 'CommonsBooking v.' . COMMONSBOOKING_VERSION . ' Contact: mail@commonsbooking.org';
		$defaultReferer   = get_site_url();

		$client = new Client(
			null,
			null,
			[
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
			]
		);

		$provider = Nominatim::withOpenStreetMapServer(
			$client,
			$defaultUserAgent,
			$defaultReferer,
		);
		$geoCoder = new StatefulGeocoder( $provider, 'en' );

		try {
			$addresses = $geoCoder->geocodeQuery( GeocodeQuery::create( $addressString ) );
			if ( ! $addresses->isEmpty() ) {
				return $addresses->first();
			}
		} catch ( Exception $exception ) {
			// Nothing to do in this case
		}

		return null;
	}
}
