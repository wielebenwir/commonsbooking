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
	 * @return array<int, string>
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
	 * @param array<int, \stdClass> $posts
	 *
	 * @return array<int, \WP_Post|null>
	 */
	public static function flattenWpdbResult( array $posts ): array {
		return array_map(
			function ( $post ) {
				return get_post( $post->ID );
			},
			$posts
		);
	}

	/**
	 * @param string $dateString
	 *
	 * @return bool
	 */
	public static function isValidDateString( string $dateString ): bool {
		return preg_match( '/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/i', $dateString ) === 1;
	}

	/**
	 * Returns array with IDs.
	 *
	 * @param array<int, \WP_Post|\CommonsBooking\Model\CustomPost> $posts
	 *
	 * @return array<int, int>
	 */
	public static function getPostIdArray( array $posts ): array {
		return array_map(
			function ( $post ) {
				return intval( $post->ID );
			},
			$posts
		);
	}

	/**
	 * Returns all post ids which are in relation to $postId.
	 * Why? Needed to get tags for cache invalidation.
	 *
	 * @param int $postId
	 *
	 * @return string[]
	 */
	public static function getRelatedPostIds( int $postId ): array {
		$postIds = [];
		$post    = get_post( $postId );

		switch ( $post->post_type ) {
			case Booking::$postType:
				$postIds = self::getRelatedPostsIdsForBooking( $postId );
				break;
			case Item::$postType:
				$postIds = self::getRelatedPostsIdsForItem( $postId );
				break;
			case Location::$postType:
				$postIds = self::getRelatedPostsIdsForLocation( $postId );
				break;
			case Restriction::$postType:
				$postIds = self::getRelatedPostsIdsForRestriction( $postId );
				break;
			case \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType:
				$postIds = self::getRelatedPostsIdsForTimeframe( $postId );
				break;
		}

		// Remove empty tags
		$postIds = array_filter( $postIds );

		return array_map( 'strval', $postIds );
	}

	/**
	 * Returns all post ids in relation to $postId.
	 *
	 * @param int $postId
	 *
	 * @return int[]
	 * @throws \CommonsBooking\Psr\Cache\InvalidArgumentException|\CommonsBooking\Psr\Cache\CacheException
	 */
	public static function getRelatedPostsIdsForLocation( int $postId ) {
		$timeframes   = \CommonsBooking\Repository\Timeframe::get( [ $postId ] );
		$restrictions = \CommonsBooking\Repository\Restriction::get( [ $postId ] );
		return array_merge(
			[ $postId ],
			self::getPostIdArray( $timeframes ),
			self::getPostIdArray( $restrictions )
		);
	}

	/**
	 * Returns all post ids in relation to $postId.
	 * CAREFUL: This will not get the location that the item is in relation to.
	 *
	 * @param int $postId
	 *
	 * @return int[]
	 * @throws \CommonsBooking\Psr\Cache\InvalidArgumentException|\CommonsBooking\Psr\Cache\CacheException
	 */
	public static function getRelatedPostsIdsForItem( int $postId ): array {
		$timeframes   = \CommonsBooking\Repository\Timeframe::get( [], [ $postId ] );
		$restrictions = \CommonsBooking\Repository\Restriction::get( [], [ $postId ] );
		return array_merge(
			[ $postId ],
			self::getPostIdArray( $timeframes ),
			self::getPostIdArray( $restrictions )
		);
	}

	/**
	 * Returns all post ids in relation to $postId.
	 *
	 * @param int $postId
	 *
	 * @return int[]
	 * @throws \Exception
	 */
	public static function getRelatedPostsIdsForTimeframe( int $postId ): array {
		$timeframe = new Timeframe( $postId );
		$ids       = [ $postId ];
		return array_merge( $ids, $timeframe->getItemIDs(), $timeframe->getLocationIDs() );
	}

	/**
	 * Returns all post ids in relation to $postId.
	 *
	 * @param int $postId
	 *
	 * @return int[]
	 * @throws \Exception
	 */
	public static function getRelatedPostsIdsForBooking( int $postId ): array {
		$booking = new \CommonsBooking\Model\Booking( $postId );
		$ids     = [ $postId ];

		if ( $booking->getItem() ) {
			$ids[] = $booking->getItemID();
		}
		if ( $booking->getLocation() ) {
			$ids[] = $booking->getLocationID();
		}
		if ( $booking->getBookableTimeFrame() ) {
			$ids[] = $booking->getBookableTimeFrame()->ID;
		}

		return $ids;
	}

	/**
	 * Returns all post ids in relation to $postId.
	 *
	 * @param int $postId
	 *
	 * @return int[]
	 * @throws \CommonsBooking\Psr\Cache\InvalidArgumentException
	 */
	public static function getRelatedPostsIdsForRestriction( int $postId ): array {
		$restriction = new \CommonsBooking\Model\Restriction( $postId );

		// Restriction itself
		$relatedPostIds = [ $postId ];

		// Item and related timeframes
		if ( $itemId = $restriction->getItemId() ) {
			$timeframes       = \CommonsBooking\Repository\Timeframe::get( [], [ $itemId ] );
			$relatedPostIds[] = $itemId;
			$relatedPostIds   = array_merge( $relatedPostIds, self::getPostIdArray( $timeframes ) );
		}

		// Location and related timeframes
		if ( $locationId = $restriction->getLocationId() ) {
			$timeframes       = \CommonsBooking\Repository\Timeframe::get( [ $locationId ] );
			$relatedPostIds[] = $locationId;
			$relatedPostIds   = array_merge( $relatedPostIds, self::getPostIdArray( $timeframes ) );
		}

		return array_unique(
			array_map( 'intval', $relatedPostIds )
		);
	}

	/**
	 * Returns a list of cache tags related to $posts, $items and $locations.
	 *
	 * @param array<int, \WP_Post|\CommonsBooking\Model\CustomPost> $posts
	 * @param array<int, int|string>                                $items
	 * @param array<int, int|string>                                $locations
	 *
	 * @return array<int, int|string>
	 */
	public static function getTags( array $posts, array $items = [], array $locations = [] ): array {
		$itemsAndLocations = self::getLocationAndItemIdsFromPosts( $posts );

		if ( ! count( $items ) && ! count( $locations ) ) {
			$items[] = 'misc';
		}

		return array_values(
			array_unique(
				array_merge(
					self::getPostIdArray( $posts ),
					$itemsAndLocations,
					$items,
					$locations
				)
			)
		);
	}

	/**
	 * Returns an array of post ids of locations and items from posts.
	 * The only posts that have items / locations assinged are timeframes and bookings.
	 * Any other posts are skipped.
	 *
	 * @param array<int, \WP_Post|\CommonsBooking\Model\CustomPost> $posts
	 *
	 * @return array<int, int>
	 */
	public static function getLocationAndItemIdsFromPosts( array $posts ): array {
		$itemsAndLocations = [];
		array_walk(
			$posts,
			function ( $timeframe ) use ( &$itemsAndLocations ) {
				// only run for timeframe or booking
				if ( ! in_array( $timeframe->post_type, [ \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType, Booking::$postType ] ) ) {
					return;
				}
				if ( ! $timeframe instanceof Timeframe ) {
					if ( $timeframe->post_type == Booking::$postType ) {
						$timeframe = new \CommonsBooking\Model\Booking( $timeframe );
					} elseif ( $timeframe->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType ) {
						$timeframe = new Timeframe( $timeframe );
					}
				}
				/** @var Timeframe $timeframe */
				$itemsAndLocations = array_merge(
					$itemsAndLocations,
					$timeframe->getItemIDs(),
					$timeframe->getLocationIDs()
				);
			}
		);
		return array_map( 'intval', $itemsAndLocations );
	}

	/**
	 * This would theoretically work if the timestamp we get from the database is in UTC.
	 * The problem is, that the timestamp is in the local timezone of the server.
	 * If we convert it to UTC, we get the wrong date and everything breaks.
	 *
	 * @param int|string $timestamp numeric value
	 *
	 * @return DateTime
	 * @throws \Exception
	 */
	public static function getUTCDateTimeByTimestamp( $timestamp ): DateTime {
		$dto = new DateTime();
		$dto->setTimestamp(
			intval( $timestamp )
		);
		$dto->setTimezone( new \DateTimeZone( 'UTC' ) );

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

	/**
	 * @param string $datetime
	 *
	 * @return DateTime
	 * @throws \Exception
	 */
	public static function getUTCDateTime( string $datetime = 'now' ): DateTime {
		$dto = new DateTime( $datetime );
		$dto->setTimezone( new \DateTimeZone( 'UTC' ) );

		return $dto;
	}

	/**
	 * UNUSED
	 *
	 * @param int $timestamp
	 * @return DateTime
	 * @throws \DateInvalidTimeZoneException
	 * @deprecated since 2.11, to be removed in 2.12
	 */
	public static function getLocalDateTime( int $timestamp ): DateTime {
		$dto = new DateTime();
		$dto->setTimestamp(
			$timestamp
		);
		$dto->setTimezone( new \DateTimeZone( wp_timezone_string() ) );

		return $dto;
	}
}
