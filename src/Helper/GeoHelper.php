<?php


namespace CommonsBooking\Helper;


use Geocoder\Exception\Exception;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle6\Client;

class GeoHelper {

	/**
	 * @param $addressString
	 *
	 * @return NominatimAddress
	 * @throws Exception
	 */
	public static function getAddressData( $addressString ) {
		$provider = Nominatim::withOpenStreetMapServer(
			new Client(),
			$_SERVER['HTTP_USER_AGENT'] );
		$geoCoder = new StatefulGeocoder( $provider, 'en' );

		$addresses = $geoCoder->geocodeQuery( GeocodeQuery::create( $addressString ) );
		if ( ! $addresses->isEmpty() ) {
			return $addresses->first();
		}
	}

}
