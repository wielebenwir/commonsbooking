<?php


namespace CommonsBooking\Helper;

use CommonsBooking\API\Share;
use CommonsBooking\Repository\ApiShares;

class API {


	/**
	 * Triggers requests to all shares with push url.
	 *
	 * @return void
	 */
 	public static function triggerPushUrls(): void {
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
	 *
	 * @return void
	 */
	public static function triggerPushUrl( Share $share ): void {
		$requestData = [
			'API_KEY' => $share->getKey(),
			'OWNER' => $share->getOwner(),
		];
		wp_remote_post( $share->getPushUrl(), $requestData );
	}
}
