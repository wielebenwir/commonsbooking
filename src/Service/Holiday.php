<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\HolidayAPINotWorkingExceptionException;
use CommonsBooking\Plugin;

class Holiday {
	use Cache;
	const BASE_URL = 'https://feiertage-api.de/api/';

	const STATE_BADEN_WUERTEMBERG = 'BW';
	const STATE_BAYERN = 'BY';
	const STATE_BERLIN = 'BE';
	const STATE_BRANDENBURG = 'BB';
	const STATE_BREMEN = 'HB';
	const STATE_HAMBURG = 'HH';
	const STATE_HESSEN = 'HE';
	const STATE_MECKLENBURG_VORPOMMERN = 'MV';
	const STATE_NIEDERSACHSEN = 'NI';
	const STATE_NORDRHEIN_WESTPHALEN = 'NW';
	const STATE_RHEINLAND_PFALZ = 'RP';
	const STATE_SAARLAND = 'SL';
	const STATE_SACHSEN = 'SN';
	const STATE_SACHSEN_ANHALT = 'ST';
	const STATE_SCHLESWIG_HOLSTEIN = 'SH';
	const STATE_THUERINGEN = 'TH';
	const STATE_NATIONAL = 'NATIONAL';


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

		throw new HolidayAPINotWorkingExceptionException(  );
	}


	private static function getConstants() {
		$oClass = new \ReflectionClass(self::Class);
		return $oClass->getConstants();
	}

	static function returnStates(){
		$consts = self::getConstants();
		$returnStates = array();
		foreach ($consts as $constname => $constvalue) {
			if($constname !== "BASE_URL")
				$returnStates[$constvalue] = str_replace('_',' ',str_replace('STATE_','',$constname));
		}
		return $returnStates;
	}
}