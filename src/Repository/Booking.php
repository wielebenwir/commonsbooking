<?php


namespace CommonsBooking\Repository;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;
use WP_Query;

class Booking extends PostRepository {

	/**
	 * Returns 0:00 timestamp for day of $timestamp.
	 *
	 * @param $timestamp
	 *
	 * @return false|int
	 */
	protected static function getStartTimestamp( $timestamp ) {
		return strtotime( 'midnight', $timestamp );
	}

	/**
	 * Returns 23:59 timestamp for day of $timestamp.
	 *
	 * @param $startTimestamp
	 *
	 * @return false|int
	 */
	protected static function getEndTimestamp( $startTimestamp ) {
		return strtotime( '+23 Hours +59 Minutes +59 Seconds', $startTimestamp );
	}

	/**
	 * Returns bookings ending at day of timestamp.
	 *
	 * @param int   $timestamp
	 * @param array $customArgs
	 *
	 * @return \CommonsBooking\Model\Booking[]
	 * @throws Exception
	 */
	public static function getEndingBookingsByDate( int $timestamp, array $customArgs = [] ): array {
		$startTimestamp = self::getStartTimestamp( $timestamp );
		$endTimestamp   = self::getEndTimestamp( $startTimestamp );

		// Default query
		$args = array(
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
					'value'   => $endTimestamp,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
					'value'   => $startTimestamp,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				array(
					'key'     => 'type',
					'value'   => Timeframe::BOOKING_ID,
					'compare' => '=',
				),
			),
			'post_status' => array( 'confirmed', 'unconfirmed' ),
			'nopaging'    => true,
		);

		// Overwrite args with passed custom args
		$args = array_merge( $args, $customArgs );

		return self::getModelsFromQuery( $args );
	}

	/**
	 * Returns bookings beginning at day of timestamp.
	 *
	 * @param int   $timestamp
	 * @param array $customArgs
	 *
	 * @return \CommonsBooking\Model\Booking[]
	 * @throws Exception
	 */
	public static function getBeginningBookingsByDate( int $timestamp, array $customArgs = [] ): array {
		$startTimestamp = self::getStartTimestamp( $timestamp );
		$endTimestamp   = self::getEndTimestamp( $startTimestamp );

		// Default query
		$args = array(
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => $endTimestamp,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => $startTimestamp,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				array(
					'key'     => 'type',
					'value'   => Timeframe::BOOKING_ID,
					'compare' => '=',
					'type'    => 'numeric',
				),
			),
			'post_status' => array( 'confirmed' ),
			'nopaging'    => true,
		);

		// Overwrite args with passed custom args
		$args = array_merge( $args, $customArgs );

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			foreach ( $posts as &$post ) {
				$post = new \CommonsBooking\Model\Booking( $post );
			}
			/** @var \CommonsBooking\Model\Booking[] $posts */
			return $posts;
		}

		return [];
	}

	/**
	 * @param int $startDateTimestamp
	 * @param int $endDateTimestamp
	 * @param int $locationId
	 * @param int $itemId
	 *
	 * @return null|\CommonsBooking\Model\Booking
	 * @throws Exception
	 */
	public static function getByDate( int $startDateTimestamp, int $endDateTimestamp, int $locationId, int $itemId ): ?\CommonsBooking\Model\Booking {
		// Default query
		$args = array(
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => $startDateTimestamp,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
					'value'   => $endDateTimestamp,
					'compare' => '=',
				),
				array(
					'key'     => 'type',
					'value'   => Timeframe::BOOKING_ID,
					'compare' => '=',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::META_LOCATION_ID,
					'value'   => $locationId,
					'compare' => '=',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::META_ITEM_ID,
					'value'   => $itemId,
					'compare' => '=',
				),
			),
			'post_status' => array( 'confirmed', 'unconfirmed' ),
			'nopaging'    => true,
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			$posts = array_filter(
				$posts,
				function ( $post ) {
					return in_array( $post->post_status, array( 'confirmed', 'unconfirmed' ) );
				}
			);

			// If there is exactly one result, return it.
			if ( count( $posts ) == 1 ) {
				$booking = new \CommonsBooking\Model\Booking( $posts[0] );
				if ( in_array( $booking->getPost()->post_status, array( 'confirmed', 'unconfirmed' ) ) ) {
					return $booking;
				}
			} elseif ( count( $posts ) > 1 ) {
				// This shouldn't happen.
				throw new Exception( __CLASS__ . '::' . __LINE__ . ': Found more than one bookings' );
			}
		}

		return null;
	}

	/**
	 * @param $startDate int
	 * @param $endDate int
	 * @param $locationId
	 * @param $itemId
	 * @param array         $customArgs
	 * @param array         $postStatus
	 *
	 * @return \CommonsBooking\Model\Booking[]
	 * @throws Exception
	 */
	public static function getByTimerange(
		int $startDate,
		int $endDate,
		$locationId = null,
		$itemId = null,
		array $customArgs = [],
		array $postStatus = [ 'confirmed', 'unconfirmed' ]
	): array {
		// Default query
		$args = array(
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => $endDate,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
					'value'   => $startDate,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				array(
					'key'     => 'type',
					'value'   => Timeframe::BOOKING_ID,
					'compare' => '=',
				),
			),
			'post_status' => $postStatus,
			'nopaging'    => true,
		);

		if ( $locationId ) {
			$args['meta_query'][] = array(
				'key'     => 'location-id',
				'value'   => $locationId,
				'compare' => '=',
			);
		}

		if ( $itemId ) {
			$args['meta_query'][] = array(
				'key'     => 'item-id',
				'value'   => $itemId,
				'compare' => '=',
			);
		}

		// Overwrite args with passed custom args
		$args = array_merge( $args, $customArgs );

		return self::getModelsFromQuery( $args );
	}

	/**
	 * Returns all bookings, allowed to see for user.
	 *
	 * @param bool $asModel if true, returns as Booking array, if false, return int array (defaults to false)
	 * @param null $minTimestamp
	 *
	 * @return \CommonsBooking\Model\Booking[]|int[]
	 * @throws Exception
	 */
	public static function getForUser(
		\WP_User $user,
		bool $asModel = false,
		$minTimestamp = null,
		array $postStatus = [ 'canceled', 'confirmed', 'unconfirmed' ]
	): array {
		$customId  = $user->ID;
		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$posts = self::get(
				[],
				[],
				null,
				$asModel,
				$minTimestamp,
				$postStatus
			);
			if ( $posts ) {
				// Check if it is the main query and one of our custom post types
				$posts = array_filter(
					$posts,
					function ( $post ) use ( $user ) {
						return commonsbooking_isUserAllowedToSee( $post, $user );
					}
				);
			}

			Plugin::setCacheItem(
				$posts,
				Wordpress::getTags( $posts ),
				$customId
			);
		}

		return $posts;
	}

	/**
	 * Returns all bookings, allowed to see/edit for current user.
	 *
	 * @param bool $asModel
	 * @param null $startDate
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getForCurrentUser(
		bool $asModel = false,
		$startDate = null,
		$postStatus = [ 'canceled', 'confirmed', 'unconfirmed' ]
	): array {
		if ( ! is_user_logged_in() ) {
			return [];
		}

		$current_user = wp_get_current_user();

		return self::getForUser( $current_user, $asModel, $startDate, $postStatus );
	}

	/**
	 * Returns bookings. This uses the CommonsBooking\Repository\Timeframe::get() method which
	 * is not based on the WP_Query class but will perform its own SQL query.
	 *
	 * @param array        $locations
	 * @param array        $items
	 * @param string|null  $date Date-String in format YYYY-mm-dd
	 * @param bool         $returnAsModel if true, returns booking model, if false return int array (defaults to false)
	 * @param $minTimestamp
	 * @param array        $postStatus
	 *
	 * @return int[]|\CommonsBooking\Model\Booking[]
	 * @throws Exception
	 */
	public static function get(
		array $locations = [],
		array $items = [],
		?string $date = null,
		bool $returnAsModel = false,
		int $minTimestamp = null,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		return \CommonsBooking\Repository\Timeframe::get(
			$locations,
			$items,
			[ Timeframe::BOOKING_ID ],
			$date,
			$returnAsModel,
			$minTimestamp,
			$postStatus
		);
	}

	/**
	 * We use this function instead of the getForUser() function when we need to paginate the results.
	 * This is to prevent timeouts for bigger queries such as data exports. As opposed to the getForUser() function,
	 * this function will use the WP_Query class to perform the query allowing us to use the pagination features of WP_Query.
	 *
	 * @param \WP_User $user The user for which to get the bookings.
	 * @param int      $page The current page that is processed.
	 * @param int      $perPage The number of bookings per page. A lower number will result in faster queries.
	 * @param array    $customArgs Valid WP_Query args array.
	 *
	 * @return \CommonsBooking\Model\Booking[] An array of Booking models.
	 */
	public static function getForUserPaginated(
		\WP_User $user,
		int $page = 1,
		int $perPage = 10,
		$customArgs = [],
		$postStatus = [ 'confirmed', 'unconfirmed', 'canceled', 'publish', 'inherit' ]
	): array {
		$args = array(
			'author'         => $user->ID,
			'post_type'      => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'meta_query'     => array(
				array(
					'key'     => 'type',
					'value'   => Timeframe::BOOKING_ID,
					'compare' => '=',
				),
			),
			'post_status'    => $postStatus,
			'posts_per_page' => $perPage,
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		// Overwrite args with passed custom args
		$args = array_merge( $args, $customArgs );

		return self::getModelsFromQuery( $args );
	}

	/**
	 * Gets all bookings that are affected by the given restriction.
	 *
	 * @param \CommonsBooking\Model\Restriction $restriction
	 *
	 * @return \CommonsBooking\Model\Booking[]|null
	 * @throws Exception
	 */
	public static function getByRestriction( \CommonsBooking\Model\Restriction $restriction ): ?array {
		return self::getByTimerange(
			$restriction->getStartDate(),
			$restriction->getEndDate(),
			$restriction->getLocationId(),
			$restriction->getItemId(),
			[],
			[ 'confirmed' ]
		);
	}

	/**
	 * Returns Array of overlapping Bookings as Booking-Model in relation to given $postID or given booking parameters
	 * This is used to check if the given parameters are overlapping with existing bookings.
	 * The given $postID will be excluded from the result so that the given booking will not be counted as overlapping.
	 *
	 * @param $itemId
	 * @param $locationId
	 * @param $startDate
	 * @param $endDate
	 * @param null       $postId
	 *
	 * @return \CommonsBooking\Model\Booking[] empty array if none are found
	 */
	public static function getExistingBookings( $itemId, $locationId, $startDate, $endDate, $postId = null ): array {

		// Get existing bookings for defined parameters
		$existingBookingsInRange = self::getByTimerange(
			$startDate,
			$endDate,
			$locationId,
			$itemId
		);

		// remove the given $postID from result
		// remove the given $postID from result
		foreach ( $existingBookingsInRange as $key => $val ) {
			if ( $val->ID === $postId ) {
				unset( $existingBookingsInRange[ $key ] );
			}
		}

		return $existingBookingsInRange;
	}

	/**
	 * Will take a valid WP_Query args array and return an array of Booking models.
	 *
	 * @param array $args
	 *
	 * @return \CommonsBooking\Model\Booking[]
	 * @throws Exception
	 */
	private static function getModelsFromQuery( array $args ): array {
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$posts = $query->get_posts();

			// Filter by post_status, query seems not to work reliable
			$posts = array_filter(
				$posts,
				function ( $post ) use ( $args ) {
					return in_array( $post->post_status, $args['post_status'] );
				}
			);

			foreach ( $posts as &$post ) {
				$post = new \CommonsBooking\Model\Booking( $post );
			}

			/** @var \CommonsBooking\Model\Booking[] $posts */
			return $posts;
		}

		return [];
	}

	/**
	 * Returns bookings for location and / or item that don't have a corresponding timeframe
	 * Will only consider bookings in the future
	 * This function is used to find orphaned bookings due to moving a location.
	 * It however will also show bookings whose corresponding timeframe has been shortened,
	 * and therefore do not have a valid timeframe anymore.
	 *
	 * @param int|null $startdate
	 * @param int[]    $items
	 * @param int[]    $locations
	 *
	 * @return \CommonsBooking\Model\Booking[]|null
	 * @throws Exception
	 */
	public static function getOrphaned(
		int $startdate = null,
		array $items = [],
		array $locations = []
	): ?array {
		$startdate = $startdate ? $startdate : time(); // set startdate to now when no startdate is defined

		$bookings = self::get( $locations, $items, null, true, $startdate, [ 'confirmed' ] );

		// check for bookings where location does not exist anymore

		$bookings = array_filter(
			$bookings,
			function ( $booking ) {
				if ( $booking->getBookableTimeFrame() ) {
					return false;
				} else {
					return true;
				}
			}
		);

		return $bookings;
	}
}
