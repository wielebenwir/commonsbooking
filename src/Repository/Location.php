<?php

namespace CommonsBooking\Repository;

use Exception;

class Location extends BookablePost {

	/**
	 * Returns array with locations for item based on bookable timeframes.
	 *
	 * @param int  $itemId
	 * @param bool $bookable
	 *
	 * @return WP_Post[]
	 * @throws Exception
	 */
	public static function getByItem( int $itemId, bool $bookable = false ): array {
		return self::getByRelatedPost( $itemId, 'item', 'location', $bookable );
	}

	/**
	 * @return string
	 */
	protected static function getPostType(): string {
		return \CommonsBooking\Wordpress\CustomPostType\Location::getPostType();
	}

	/**
	 * @return string
	 */
	protected static function getTaxonomyName() {
		return \CommonsBooking\Wordpress\CustomPostType\Location::getTaxonomyName();
	}

	/**
	 * This is the model class that belongs to the post type.
	 * With the model class, you are able to perform additional functions on the post type.
	 *
	 * @return string
	 */
	protected static function getModelClass(): string {
		return \CommonsBooking\Model\Location::class;
	}
}
