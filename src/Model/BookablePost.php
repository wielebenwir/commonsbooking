<?php


namespace CommonsBooking\Model;


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
			$bookableTimeframes = Timeframe::get(
				[ $this->ID ],
				$items,
				[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
				$this->getDate() ?: $date,
				$asModel,
				time()
			);

		}
		if ( get_called_class() == Item::class ) {
			$bookableTimeframes = Timeframe::get(
				$locations,
				[ $this->ID ],
				[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
				$this->getDate() ?: $date,
				$asModel,
				time()
			);
		}

		return $bookableTimeframes;
	}

	/**
	 * Returns bookable timeframes for a specific location
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isBookable(): bool {
		return count( $this->getBookableTimeframes() ) > 0;
	}

}
