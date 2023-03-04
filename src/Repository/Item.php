<?php

namespace CommonsBooking\Repository;

use Exception;

class Item extends BookablePost {

	/**
	 * Returns array with items at location based on bookable timeframes.
	 *
	 * @param $locationId
	 *
	 * @param bool $bookable
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getByLocation( $locationId, bool $bookable = false ): array {
		return self::getByRelatedPost( $locationId, 'location', 'item', $bookable );
	}

	/**
	 * @return string
	 */
	protected static function getPostType(): string {
		return \CommonsBooking\Wordpress\CustomPostType\Item::getPostType();
	}

	/**
	 * @return string
	 */
	protected static function getModelClass(): string {
		return \CommonsBooking\Model\Item::class;
	}

}
