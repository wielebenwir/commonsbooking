<?php


namespace CommonsBooking\Helper;

use CommonsBooking\API\Share;
use CommonsBooking\Repository\ApiShares;

class API {


	/**
	 * Triggers requests to all shares with push url.
	 */
	public static function triggerPushUrls() {
		$apiShares = ApiShares::getAll();

		foreach ( $apiShares as $apiShare ) {
			if ( $apiShare->getPushUrl() ) {
				self::triggerPushUrl( $apiShare );
			}
		}
	}

	/**
	 * Makes a post request with api-key and owner to the configured push url.
	 *
	 * @param Share $share
	 */
	public static function triggerPushUrl( Share $share ) {
		$requestData = array(
			'API_KEY' => $share->getKey(),
			'OWNER' => $share->getOwner(),
		);
		wp_remote_post( $share->getPushUrl(), $requestData );
	}
}
