<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use DateTime;
use function get_pages;

class Wordpress {

	/**
	 * @return array
	 */
	public static function getPageListTitle(): array {
		$pages    = get_pages();
		$pagelist = [];

		if ( $pages ) {
			foreach ( $pages as $value ) {
				$pagelist[ $value->ID ] = $value->post_title;
			}
		}

		return $pagelist;
	}

	/**
	 * Flatten array and return it.
	 *
	 * @param $posts
	 *
	 * @return array|array[]|null[]|WP_Post[]
	 */
	public static function flattenWpdbResult( $posts ): array {
		return array_map( function ( $post ) {
			return get_post( $post->ID );
		}, $posts );
	}

	/**
	 * @param $dateString
	 *
	 * @return bool|false
	 */
	public static function isValidDateString($dateString): bool {
		return preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/i',$dateString) === 1;
	}

	/**
	 * Returns array with IDs.
	 * @param $posts
	 *
	 * @return array
	 */
	public static function getPostIdArray($posts): array {
		 return array_map( function ( $post ) {
			return strval($post->ID);
		}, $posts);
	}

	/**
	 * Returns all post ids which are in relation to $postId.
	 * Why? Needed to get tags for cache invalidation.
	 *
	 * @param $postId
	 *
	 * @return array|string[]
	 */
	public static function getRelatedPostIds($postId): array {
		$postIds = [];
		$post = get_post($postId);

		switch ($post->post_type) {
			case Booking::$postType:
				$postIds = self::getRelatedPostsIdsForBooking($postId);
				break;
			case Item::$postType:
				$postIds = self::getRelatedPostsIdsForItem($postId);
				break;
			case Location::$postType:
				$postIds = self::getRelatedPostsIdsForLocation($postId);
				break;
			case Restriction::$postType:
				$postIds = self::getRelatedPostsIdsForRestriction($postId);
				break;
			case \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType:
				$postIds = self::getRelatedPostsIdsForTimeframe($postId);
				break;
		}

		// Remove empty tags
		$postIds = array_filter($postIds);

		return array_map( function ( $postId ) {
			return strval($postId);
		}, $postIds);
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * @param $postId
	 *
	 * @return mixed
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getRelatedPostsIdsForLocation($postId) {
		$timeframes = \CommonsBooking\Repository\Timeframe::get([$postId]);
		$restrictions = \CommonsBooking\Repository\Restriction::get([$postId]);
		return array_merge(
			[$postId],
			Wordpress::getPostIdArray($timeframes),
			Wordpress::getPostIdArray($restrictions)
		);
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * @param $postId
	 *
	 * @return array
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getRelatedPostsIdsForItem($postId): array {
		$timeframes = \CommonsBooking\Repository\Timeframe::get([], [$postId]);
		$restrictions = \CommonsBooking\Repository\Restriction::get([], [$postId]);
		return array_merge(
			[$postId],
			Wordpress::getPostIdArray($timeframes),
			Wordpress::getPostIdArray($restrictions)
		);
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * @param $postId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getRelatedPostsIdsForTimeframe($postId): array {
		$timeframe = new Timeframe($postId);
		$ids = [$postId];

		if($timeframe->getItem()) {
			$ids[] = $timeframe->getItem()->ID;
		}
		if($timeframe->getLocation()) {
			$ids[] = $timeframe->getLocation()->ID;
		}

		return $ids;
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * @param $postId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getRelatedPostsIdsForBooking($postId): array {
		$booking = new \CommonsBooking\Model\Booking($postId);
		$ids = [$postId];

		if($booking->getItem()) {
			$ids[] = $booking->getItem()->ID;
		}
		if($booking->getLocation()) {
			$ids[] = $booking->getLocation()->ID;
		}
		if($booking->getBookableTimeFrame()) {
			$ids[] = $booking->getBookableTimeFrame()->ID;
		}

		return $ids;
	}

	/**
	 * Returns all post ids in relation to $postId.
	 *
	 * @param $postId
	 *
	 * @return array
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getRelatedPostsIdsForRestriction($postId): array {
		$restriction = new \CommonsBooking\Model\Restriction($postId);

		// Restriction itself
		$relatedPostIds = [ $postId ];

		// Item and related timeframes
		if($itemId = $restriction->getItemId()) {
			$timeframes = \CommonsBooking\Repository\Timeframe::get([], [$itemId]);
			$relatedPostIds[] = $itemId;
			$relatedPostIds = array_merge($relatedPostIds, Wordpress::getPostIdArray($timeframes));
		}

		// Location and related timeframes
		if($locationId = $restriction->getLocationId()) {
			$timeframes = \CommonsBooking\Repository\Timeframe::get([$locationId]);
			$relatedPostIds[] = $locationId;
			$relatedPostIds = array_merge($relatedPostIds, Wordpress::getPostIdArray($timeframes));
		}

		return $relatedPostIds;
	}

	/**
	 * Returns a list of cache tags related to $posts, $items and $locations.
	 *
	 * @param $posts
	 * @param array $items
	 * @param array $locations
	 *
	 * @return array
	 */
	public static function getTags($posts, array $items = [], array $locations = []) {
		$itemsAndLocations = Wordpress::getLocationAndItemIdsFromPosts($posts);

		if(!count($items) && !count($locations)) {
			$items[] = 'misc';
		}

		return array_values(array_unique(
				array_merge(
					Wordpress::getPostIdArray($posts),
					$itemsAndLocations,
					$items,
					$locations
				))
		);
	}

	/**
	 * Returns an array of post ids of locations and items from posts.
	 * @param $posts
	 *
	 * @return array
	 */
	public static function getLocationAndItemIdsFromPosts($posts) {
		$itemsAndLocations = [];
		array_walk($posts, function ($timeframe) use (&$itemsAndLocations) {
			$itemsAndLocations[] = get_post_meta(
				$timeframe->ID,
				Timeframe::META_ITEM_ID,
				true
			);
			$itemsAndLocations[] = get_post_meta(
				$timeframe->ID,
				Timeframe::META_LOCATION_ID,
				true
			);
		});
		return $itemsAndLocations;
	}

	/**
	 * This would theoretically work if the timestamp we get from the database is in UTC.
	 * The problem is, that the timestamp is in the local timezone of the server.
	 * If we convert it to UTC, we get the wrong date and everything breaks.
	 *
	 * @param $timestamp
	 *
	 * @return DateTime
	 * @throws \Exception
	 */
	public static function getUTCDateTimeByTimestamp($timestamp) {
		$dto = new DateTime();
		$dto->setTimestamp(
			intval( $timestamp )
		);
		$dto->setTimezone(new \DateTimeZone('UTC'));

		return $dto;
	}

	/**
	 * This function does what probably the getUTCDateTimeByTimestamp was originally supposed to do.
	 *
	 * @param int $timestamp
	 *
	 * @return DateTime
	 * @throws \Exception
	 */
	public static function convertTimestampToUTCDatetime( $timestamp ) {
		$datetime = date( 'Y-m-d H:i:s', $timestamp );
		$dto      = new DateTime( $datetime, new \DateTimeZone( wp_timezone_string() ) );
		$dto->setTimezone( new \DateTimeZone( 'UTC' ) );

		return $dto;
	}

	public static function getUTCDateTime($datetime = 'now') {
		$dto = new DateTime($datetime);
		$dto->setTimezone(new \DateTimeZone('UTC'));

		return $dto;
	}

	public static function getLocalDateTime($timestamp) {
		$dto = new DateTime();
		$dto->setTimestamp(
			$timestamp
		);
		$dto->setTimezone(new \DateTimeZone(wp_timezone_string()));

		return $dto;
	}
}
