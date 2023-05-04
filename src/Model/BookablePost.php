<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Exception;

/**
 * Logical wrapper for `bookable` (timeframe) posts
 */
class BookablePost extends CustomPost {

	/**
	 * Returns timeframes available for booking, for a given set of locations and a set of items and optional point
	 * in time.
	 *
	 * @uses Timeframe::getBookableForCurrentUser()
	 *
	 * @param false $asModel
	 * @param array $locations
	 * @param array $items
	 * @param string|null $date
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBookableTimeframes(
		bool $asModel = true,
		array $locations = [],
		array $items = [],
		?string $date = null
	): array {
		$bookableTimeframes = [];
		if ( get_called_class() == Location::class ) {
			$bookableTimeframes = Timeframe::getBookableForCurrentUser(
				[ $this->ID ],
				$items,
				$this->getDate() ?: $date,
				$asModel,
				Helper::getLastFullHourTimestamp()
			);

		}
		if ( get_called_class() == Item::class ) {
			$bookableTimeframes = Timeframe::getBookableForCurrentUser(
				$locations,
				[ $this->ID ],
				$this->getDate() ?: $date,
				$asModel,
				Helper::getLastFullHourTimestamp()
			);
		}

		return $bookableTimeframes;
	}

	/**
	 * Returns if bookable timeframes are available for a specific location
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isBookable(): bool {
		return count( $this->getBookableTimeframes() ) > 0;
	}

}
