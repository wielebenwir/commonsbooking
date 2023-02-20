<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\HolidayAPINotWorkingExceptionException;
use CommonsBooking\Plugin;

class Holiday {
	use Cache;

	const BASE_URL = 'https://feiertage-api.de/api/';

	public static function getHolidayFromState() {
		$year   = $_POST['year'];
		$state  = $_POST['state'];
		$customCacheKey= md5(
			$year.$state
		);
		if ( Plugin::getCacheItem($customCacheKey) ) {
			$json = Plugin::getCacheItem($customCacheKey);
		} else {
			$result = self::_file_get_contents_t_curl( self::BASE_URL . '?jahr=' . $year . '&nur_land=' . $state );
			$json   = json_encode(
				array_map(
					function ( $val ) {
						return $val['datum'];
					},
					json_decode( $result, true ) )
			);
			Plugin::setCacheItem( $json , [], $customCacheKey);
		}
		echo $json;
		wp_die();

	}

	private static function _file_get_contents_t_curl( $url ) {
		$ctx  = stream_context_create( [ 'http' => [ 'timeout' => 5 ] ] );
		$file = @file_get_contents( $url, false, $ctx );
		if ( ! empty( $file ) ) {
			return $file;
		} else {
			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
			$data = curl_exec( $ch );
			curl_close( $ch );

			if ( empty( $data ) ) {
				throw new HolidayAPINotWorkingExceptionException(  );
			} else {
				return $data;
			}
		}
	}

	/**
	 * Returns state mapping.
	 * @return string[]
	 */
	static function returnStates(): array {
		return [
			'BW' => 'BADEN WUERTEMBERG',
			'BY' => 'BAYERN',
			'BE' => 'BERLIN',
			'BB' => 'BRANDENBURG',
			'HB' => 'BREMEN',
			'HH' => 'HAMBURG',
			'HE' => 'HESSEN',
			'MV' => 'MECKLENBURG VORPOMMERN',
			'NI' => 'NIEDERSACHSEN',
			'NW' => 'NORDRHEIN WESTPHALEN',
			'RP' => 'RHEINLAND PFALZ',
			'SL' => 'SAARLAND',
			'SN' => 'SACHSEN',
			'ST' => 'SACHSEN ANHALT',
			'SH' => 'SCHLESWIG HOLSTEIN',
			'TH' => 'THUERINGEN',
			'NATIONAL' => 'NATIONAL'
		];
	}
}