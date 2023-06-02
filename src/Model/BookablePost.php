<?php


namespace CommonsBooking\Model;


use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Exception;

class BookablePost extends CustomPost {

	/**
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
	 * Checks if a specified location or item is bookable
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isBookable(): bool {
		return count( $this->getBookableTimeframes() ) > 0;
	}

}
