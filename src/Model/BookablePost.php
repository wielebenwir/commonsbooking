<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Exception;

/**
 * Logical wrapper for `bookable` (timeframe) posts
 * Inherited by Location and Item.
 */
class BookablePost extends CustomPost {

	/**
	 * Will return an array of bookable timeframes for the current object.
	 * Since currently, BookablePost is only inherited by Location and Item, it's main purpose is to
	 * return the corresponding bookable timeframe for a location or item.
	 *
	 * @uses Timeframe::getBookableForCurrentUser()
	 *
	 * @param false       $asModel - Whether to return the timeframes as model (CommonsBooking\Model\Timeframe) or as array of WP_Post
	 * @param array       $locations - If called from Item, this array should contain the location IDs to filter the timeframes
	 * @param array       $items - If called from Location, this array should contain the item IDs to filter the timeframes
	 * @param string|null $date - A datestring to get only the timeframes that are bookable on a specific date
	 *
	 * @return array - An array of CommonsBooking\Model\Timeframe or WP_Post, an empty array if no bookable timeframes were found
	 * @throws Exception
	 */
	public function getBookableTimeframes(
		bool $asModel = true,
		array $locations = array(),
		array $items = array(),
		?string $date = null
	): array {
		$bookableTimeframes = array();
		if ( get_called_class() == Location::class ) {
			$bookableTimeframes = Timeframe::getBookableForCurrentUser(
				array( $this->ID ),
				$items,
				$this->getDate() ?: $date,
				$asModel,
				Helper::getLastFullHourTimestamp()
			);
		}
		if ( get_called_class() == Item::class ) {
			$bookableTimeframes = Timeframe::getBookableForCurrentUser(
				$locations,
				array( $this->ID ),
				$this->getDate() ?: $date,
				$asModel,
				Helper::getLastFullHourTimestamp()
			);
		}

		return $bookableTimeframes;
	}

	/**
	 * Checks if a specified location or item is bookable.
	 * Bookable means, that there is at least one associated timeframe that is bookable.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isBookable(): bool {
		return count( $this->getBookableTimeframes() ) > 0;
	}
}
