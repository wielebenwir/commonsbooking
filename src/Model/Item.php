<?php


namespace CommonsBooking\Model;


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
		return Timeframe::get(
			[ $locationId ],
			[ $this->ID ],
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			null,
			$asModel,
			time()
		);
	}
}
