<?php

namespace CommonsBooking\Repository;

use Exception;

use WP_Post;

class Item extends BookablePost {

	/**
	 * Returns array with items at location based on bookable timeframes.
	 *
	 * @param int  $locationId
	 *
	 * @param bool $bookable
	 *
	 * @return WP_Post[]
	 * @throws Exception
	 */
	public static function getByLocation( int $locationId, bool $bookable = false ): array {
		return self::getByRelatedPost( $locationId, 'location', 'item', $bookable );
	}

	/**
	 * @return string
	 */
	protected static function getPostType(): string {
		return \CommonsBooking\Wordpress\CustomPostType\Item::getPostType();
	}

	/**
	 * This is the model class that belongs to the post type.
	 * With the model class, you are able to perform additional functions on the post type.
	 *
	 * @return string
	 */
	protected static function getModelClass(): string {
		return \CommonsBooking\Model\Item::class;
	}

	/**
	 * @return string
	 */
	protected static function getTaxonomyName() {
		return \CommonsBooking\Wordpress\CustomPostType\Item::getTaxonomyName();
	}
}
