<?php


namespace CommonsBooking\Helper;


use Geocoder\Geocoder;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client;

class GeoHelper
{

    /**
     * @param $addressString
     *
     * @return NominatimAddress
     * @throws \Geocoder\Exception\Exception
     */
    public static function getAddressData($addressString)
    {
        $provider = Nominatim::withOpenStreetMapServer(
            new Client(),
            $_SERVER['HTTP_USER_AGENT']);;
        $geoCoder = new \Geocoder\StatefulGeocoder($provider, 'en');

        $addresses = $geoCoder->geocodeQuery(GeocodeQuery::create($addressString));
        if ( ! $addresses->isEmpty()) {
            return $addresses->first();
        }
    }

}
