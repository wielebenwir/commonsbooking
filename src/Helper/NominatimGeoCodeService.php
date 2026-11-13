<?php

namespace CommonsBooking\Helper;

/**
 * Geocoding via the Nominatim (OpenStreetMap) search API, using the WordPress HTTP API.
 * Helps to properly mock/unit-test 3rd-party components.
 */
class NominatimGeoCodeService implements GeoCodeService {

	private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

	/**
	 * NOTE: This uses the english locale since we only use the coordinates of the returned address.
	 */
	public function getAddressData( string $addressString ): ?GeoLocation {
		$request = [
			'url'   => self::ENDPOINT,
			'query' => [
				'q'               => $addressString,
				'format'          => 'jsonv2',
				'limit'           => 1,
				'accept-language' => 'en',
			],
			'args'  => [
				'timeout' => 10,
				'headers' => [
					'User-Agent' => 'CommonsBooking v.' . COMMONSBOOKING_VERSION . ' Contact: mail@commonsbooking.org',
					'Referer'    => get_site_url(),
				],
			],
		];

		/**
		 * Allows changing the geocoding request, e.g. to add "addressdetails=1", use a self-hosted
		 * Nominatim instance or adjust the HTTP arguments.
		 *
		 * @since 2.11.4
		 *
		 * @param array  $request       with keys 'url' (string), 'query' (array of URL parameters) and 'args' (array of wp_remote_get arguments)
		 * @param string $addressString the address that is geocoded
		 */
		$request = apply_filters( 'commonsbooking_geocode_request', $request, $addressString );

		$response = wp_remote_get( add_query_arg( $request['query'], $request['url'] ), $request['args'] );

		if ( is_wp_error( $response ) ) {
			error_log( 'CommonsBooking geocoding failed: ' . $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			error_log( 'CommonsBooking geocoding failed: HTTP ' . $code );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data[0]['lat'], $data[0]['lon'] ) ) {
			return null;
		}

		return new GeoLocation( (float) $data[0]['lat'], (float) $data[0]['lon'], $data[0] );
	}
}
