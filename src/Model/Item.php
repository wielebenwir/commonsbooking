<?php


namespace CommonsBooking\Model;


use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Exception;

class Item extends BookablePost {
	/**
	 * Returns bookable timeframes for a specific location
	 *
	 * @param $locationId
	 *
	 * @param bool $asModel
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBookableTimeframesByLocation( $locationId, bool $asModel = false ): array {
		return Timeframe::getBookableForCurrentUser(
			[ $locationId ],
			[ $this->ID ],
			null,
			$asModel,
			Helper::getLastFullHourTimestamp()
		);
	}

	/**
	 * TODO: Currently, also the author of the post
	 *       is considered to be an admin, this does not make a lot of sense and should maybe be re-considered.
	 * @return array|mixed|string[]
	 */
	public function getAdmins() {
		$itemId       = $this->ID;
		$itemAdminIds = get_post_meta( $itemId, '_' . \CommonsBooking\Wordpress\CustomPostType\Item::$postType . '_admins', true );
		if ( is_string( $itemAdminIds ) ) {
			if ( strlen( $itemAdminIds ) > 0 ) {
				$itemAdminIds = [ $itemAdminIds ];
			} else {
				$itemAdminIds = [];
			}
		}
		$itemAdminIds[] = get_post_field( 'post_author', $this->ID );

		return $itemAdminIds;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getRestrictions(): array {
		return \CommonsBooking\Repository\Restriction::get(
			[],
			[$this->ID],
			null,
			true
		);
	}

}
