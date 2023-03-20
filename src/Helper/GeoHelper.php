<?php


namespace CommonsBooking\Helper;


use Geocoder\Exception\Exception;
use Geocoder\Location;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Http\Client\Curl\Client;

class GeoHelper {

	/**
	 * @param $addressString
	 *
	 * @return ?Location
	 * @throws Exception
	 */
	public static function getAddressData( $addressString ): ?Location {
		$defaultUserAgent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0";

		$client = new Client(
			null,
			null,
			[
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
			]
		);

		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		} else {
			$userAgent = $defaultUserAgent;
		}
		$provider = Nominatim::withOpenStreetMapServer(
			$client,
			$userAgent
		);
		$geoCoder = new StatefulGeocoder( $provider, 'en' );

		try {
			$addresses = $geoCoder->geocodeQuery( GeocodeQuery::create( $addressString ) );
			if ( ! $addresses->isEmpty() ) {
				return $addresses->first();
			}
		} catch (\Exception $exception) {
			// Nothing to do in this case
		}

		return null;
	}

}
