<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Exception;

/**
 * This is the logical wrapper for the item custom post type.
 *
 * You can get the items from the database using the @see \CommonsBooking\Repository\Item class.
 *
 * Additionally, all the public functions in this class can be called using Template Tags.
 */
class Item extends BookablePost {
	/**
	 * Returns all bookable timeframes for a specific location.
	 *
	 * @param $locationId
	 *
	 * @param bool       $asModel
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBookableTimeframesByLocation( $locationId, bool $asModel = false ): array {
		return Timeframe::getBookableForCurrentUser(
			array( $locationId ),
			array( $this->ID ),
			null,
			$asModel,
			Helper::getLastFullHourTimestamp()
		);
	}

	/**
	 * Will get all the admins for this item.
	 * The admins can be configured in the backend.
	 * This will not get the admins of the location that this item belongs to. If you want that, use the function from the Model/Timeframe class.
	 *
	 * TODO: This currently includes the author of the item as an admin.
	 *       This does not make sense in all contexts and should be fixed.
	 *
	 * @return array|mixed|string[]
	 */
	public function getAdmins() {
		$itemId       = $this->ID;
		$itemAdminIds = get_post_meta( $itemId, '_' . \CommonsBooking\Wordpress\CustomPostType\Item::$postType . '_admins', true );
		if ( is_string( $itemAdminIds ) ) {
			if ( strlen( $itemAdminIds ) > 0 ) {
				$itemAdminIds = array( $itemAdminIds );
			} else {
				$itemAdminIds = array();
			}
		}
		$itemAdminIds[] = get_post_field( 'post_author', $this->ID );

		return array_values(
			array_unique(
				array_map( 'intval', $itemAdminIds )
			)
		);
	}

	/**
	 * Returns all applicable restrictions for this item.
	 *
	 * This function is not used anywhere yet.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getRestrictions(): array {
		return \CommonsBooking\Repository\Restriction::get(
			array(),
			array( $this->ID ),
			null,
			true
		);
	}
}
