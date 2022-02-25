<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
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

		return [
			$timeframe->getItem()->ID,
			$timeframe->getLocation()-ID,
			$postId
		];
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
		return [
			$booking->getItem()->ID,
			$booking->getLocation()->ID,
			$postId
		];
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * @param $postId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getRelatedPostsIdsForRestriction($postId): array {
		$restriction = new \CommonsBooking\Model\Restriction($postId);
		return [
			$restriction->getLocationId(),
			$restriction->getItemId(),
			$postId
		];
	}

}
