<?php


namespace CommonsBooking\Repository;

use CommonsBooking\API\Share;
use CommonsBooking\Settings\Settings;

/**
 * Retrieval of CommonsBooking\API\Share data
 */
class ApiShares {


	/**
	 * Returns all existing API shares.
	 *
	 * @return Share[]
	 */
	public static function getAll() {
		$apiSharesConfig = Settings::getOption( 'commonsbooking_options_api', 'api_share_group' );
		$apiShares       = array();

		if ( is_array( $apiSharesConfig ) ) {
			foreach ( $apiSharesConfig as $apiShare ) {
				$apiShares[] = new Share(
					$apiShare['api_name'],
					$apiShare['api_enabled'],
					$apiShare['push_url'],
					$apiShare['api_key'],
					get_bloginfo( 'name' )
				);
			}
		}

		return $apiShares;
	}

	/**
	 * Returns share if one exists
	 *
	 * @param string $key is an api key.
	 * @return Share|void
	 */
	public static function getByKey( $key ) {
		$apiShares = self::getAll();
		foreach ( $apiShares as $apiShare ) {
			if ( $apiShare->getKey() == $key ) {
				return $apiShare;
			}
		}
	}
}
