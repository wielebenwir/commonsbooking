<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use Exception;

class Timeframe extends PostRepository {

	/**
	 * Returns only user bookable timeframes.
	 *
	 * @param array $locations
	 * @param array $items
	 * @param string|null $date
	 * @param bool $returnAsModel
	 * @param $minTimestamp
	 * @param array $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getBookable(
		array $locations = [],
		array $items = [],
		?string $date = null,
		bool $returnAsModel = false,
		$minTimestamp = null,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		$bookableTimeframes = self::get(
			$locations,
			$items,
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			$date,
			$returnAsModel,
			$minTimestamp,
			$postStatus
		);

		$timeframesModels = $bookableTimeframes;
		if (!$returnAsModel){
			self::castPostsToModels($timeframesModels); //cast to model to do calculations
		} 
		foreach ($timeframesModels as $key => $timeframe){
			if ($timeframe->isWithRoleRestriction()) {
				unset($bookableTimeframes[$key]); //removes timeframes with role restrictions from timeframe array
			}
		}
		return $bookableTimeframes;
	}

	/**
	 * Function to get timeframes with all possible options/params.
	 * Why? We have different types of timeframes and in some cases we need multiple of them.
	 *      In this case we need this function.
	 *      Other functions use this one as base function for more specialized searches.
	 *
	 * @param array $locations
	 * @param array $items
	 * @param array $types
	 * @param string|null $date Date-String in format YYYY-mm-dd
	 *
	 * @param bool $returnAsModel
	 *
	 * @param int|null $minTimestamp
	 *
	 * @param string[] $postStatus
	 *
	 * @return array
	 * @throws Exception
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function get(
		array $locations = [],
		array $items = [],
		array $types = [],
		?string $date = null,
		bool $returnAsModel = false,
		?int $minTimestamp = null,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		if ( ! count( $types ) ) {
			$types = [
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			];
		}

		$customId = md5( serialize( $types ) );
		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {

			$posts = [];

			// Get Post-IDs considering types, items and locations
			$postIds = self::getPostIdsByType( $types, $items, $locations );

			if ( $postIds && count( $postIds ) ) {
				$posts = self::getPostsByBaseParams(
					$date,
					$minTimestamp,
					null,
					$postIds,
					$postStatus
				);
			}

			if ( $posts && count( $posts ) ) {
				$posts = self::filterTimeframes( $posts, $date );
			}

			// if returnAsModel == TRUE the result is a timeframe model instead of a wordpress object
			if ( $returnAsModel ) {
				self::castPostsToModels( $posts );
			}

			Plugin::setCacheItem( $posts, $customId );
			return $posts;
		}
	}

	/**
	 * Returns Post-IDs by type(s), item(s), location(s)
	 * Why? It's because of performance. We use the ids as base set for following filter queries.
	 *
	 * @param array $types
	 * @param array $items
	 * @param array $locations
	 *
	 * @return mixed
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getPostIdsByType( array $types = [], array $items = [], array $locations = [] ) {

		if ( ! count( $types ) ) {
			$types = [
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
            ];
		}

		$customId = md5( serialize( $types ) );
		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			global $wpdb;
			$table_postmeta = $wpdb->prefix . 'postmeta';

			$itemQuery = "";

			$items     = array_filter( $items );
			$locations = array_filter( $locations );

            // additional sanitizing. Allow only integer
            $items      = commonsbooking_sanitizeArrayorString( $items, 'intval' );
            $locations  = commonsbooking_sanitizeArrayorString( $locations, 'intval' );
            $types      = commonsbooking_sanitizeArrayorString( $types, 'intval' );


			// Query for item(s)
			if ( count( $items ) > 0 ) {
				$itemQuery = " 
                    INNER JOIN $table_postmeta pm2 ON
                        pm2.post_id = pm1.post_id AND
                        pm2.meta_key = 'item-id' AND
                        pm2.meta_value IN (" . implode( ',', $items ) . ")
                ";
			}

			// Query for location(s)
			$locationQuery = "";
			if ( count( $locations ) > 0 ) {
				$locationQuery = " 
                    INNER JOIN $table_postmeta pm3 ON
                        pm3.post_id = pm1.post_id AND
                        pm3.meta_key = 'location-id' AND
                        pm3.meta_value IN (" . implode( ',', $locations ) . ")
                ";
			}

			// Complete query, including types
			$query = "
                SELECT DISTINCT pm1.post_id from $table_postmeta pm1 
                " .
			         $itemQuery .
			         $locationQuery .
			         "   
                 WHERE
                    pm1.meta_key = 'type' AND
	                pm1.meta_value IN (" . implode( ',', $types ) . ")
            ";

			// Run query
			$posts = $wpdb->get_results( $query, ARRAY_N );

			// Get Post-IDs
			foreach ( $posts as &$post ) {
				$post = $post[0];
			}

			Plugin::setCacheItem( $posts, $customId );

			return $posts;
		}
	}

	/**
	 * Queries for posts within $postIds and filters them by $date and/or $minTimestamp and $postStatus.
	 * Why? This kind of filtering is needed nearly everywhere in commonsbooking.
	 *
	 * @param string|null $date
	 * @param int|null $minTimestamp
	 * @param int|null $maxTimestamp
	 * @param array $postIds
	 * @param array $postStatus
	 *
	 * @return array
	 */
	private static function getPostsByBaseParams( ?string $date, ?int $minTimestamp, ?int $maxTimestamp, array $postIds, array $postStatus ): array {
		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			global $wpdb;
			$table_postmeta = $wpdb->prefix . 'postmeta';
			$table_posts    = $wpdb->prefix . 'posts';
			$dateQuery      = "";

			// Filter by date
			if ( $date && ! $minTimestamp ) {
				$dateQuery = self::getFilterByDateQuery( $table_postmeta, $date );
			}

			// Filter only from a specific start date.
			// Rep-End must be > Min Date (0:00)
			if ( $minTimestamp && ! $maxTimestamp ) {
				$dateQuery = self::getFilterFromDateQuery( $table_postmeta, $minTimestamp );
			}

			// Filter for specific timerange
			if ( $minTimestamp && $maxTimestamp ) {
				$dateQuery = self::getTimerangeQuery( $table_postmeta, $minTimestamp, $maxTimestamp );
			}

			// Complete query
			$query = "SELECT DISTINCT pm1.* from $table_posts pm1
                    " . $dateQuery . "
                    WHERE
                        pm1.id in (" . implode( ",", $postIds ) . ") AND
                        pm1.post_type IN ('" . implode( "','", \CommonsBooking\Wordpress\CustomPostType\Timeframe::getSimilarPostTypes() ) . "') AND
                        pm1.post_status IN ('" . implode( "','", $postStatus ) . "')
                ";

			$posts = $wpdb->get_results( $query, ARRAY_N );
			$posts = Wordpress::flattenWpdbResult( $posts );

			Plugin::setCacheItem( $posts );

			return $posts;
		}
	}

	/**
	 * Returns query to get posts which overlap with day of $date.
	 * Conditions:
	 * - Starts before or on this day
	 * - Ends after or on this day OR has no end
	 *
	 * @param string $table_postmeta
	 * @param string $date
	 *
	 * @return string
	 */
	private static function getFilterByDateQuery( string $table_postmeta, string $date ): string {
		global $wpdb;
		return $wpdb->prepare(
			"INNER JOIN $table_postmeta pm4 ON
                pm4.post_id = pm1.id AND
                pm4.meta_key = 'repetition-start' AND
                pm4.meta_value BETWEEN 0 AND %d 
            INNER JOIN $table_postmeta pm5 ON
                pm5.post_id = pm1.id AND (
                    (
                        pm5.meta_key = '" . \CommonsBooking\Model\Timeframe::REPETITION_END . "' AND
                        pm5.meta_value BETWEEN %d AND 3000000000
                    ) OR
                    (
                        pm1.id not in (
                            SELECT post_id FROM $table_postmeta 
                            WHERE 
                                meta_key = '" . \CommonsBooking\Model\Timeframe::REPETITION_END . "'
                        )
                    )
                )                        
            ",
			strtotime( $date . 'T23:59' ),
			strtotime( $date )
		);
	}

	/**
	 * Returns query to get posts which end after $minTimestamp.
	 *
	 * @param string $table_postmeta
	 * @param int $minTimestamp
	 *
	 * @return string
	 */
	private static function getFilterFromDateQuery( string $table_postmeta, int $minTimestamp ): string {
		global $wpdb;
		return $wpdb->prepare(
			"INNER JOIN $table_postmeta pm4 ON
	            pm4.post_id = pm1.id AND (
	                ( 
	                    pm4.meta_key = '" . \CommonsBooking\Model\Timeframe::REPETITION_END . "' AND
	                    pm4.meta_value > %d
	                ) OR
	                (
	                    pm1.id not in (
	                        SELECT post_id FROM $table_postmeta 
	                        WHERE
	                            meta_key = '" . \CommonsBooking\Model\Timeframe::REPETITION_END . "'
	                    )
	                )
	            )
	        ",
			$minTimestamp
		);
	}

	/**
	 * @param string $table_postmeta
	 * @param int $minTimestamp
	 * @param int $maxTimestamp
	 *
	 * @return string
	 */
	private static function getTimerangeQuery( string $table_postmeta, int $minTimestamp, int $maxTimestamp ): string {
		global $wpdb;
		return $wpdb->prepare(
			"INNER JOIN $table_postmeta pm4 ON
	            pm4.post_id = pm1.id AND (
	                pm4.meta_key = 'repetition-start' AND
	                pm4.meta_value <= %d                  
	            )
	        INNER JOIN $table_postmeta pm5 ON
	            pm5.post_id = pm1.id AND (   
	                (                         
	                    pm5.meta_key = 'repetition-end' AND
	                    pm5.meta_value >= %d
	                ) OR (
	                    NOT EXISTS ( 
	                        SELECT * FROM $table_postmeta 
	                        WHERE
	                            meta_key = 'repetition-end' AND
	                            post_id = pm5.post_id
	                    )
	                )                          
	            )
	        ",
			$maxTimestamp,
			$minTimestamp
		);
	}

	/**
	 * Wrapper function for all filters.
	 *
	 * @param array $posts
	 * @param string|null $date
	 *
	 * @return array
	 */
	private static function filterTimeframes( array $posts, ?string $date ): array {
		// Filter by configured days
		$posts = self::filterTimeframesByConfiguredDays( $posts, $date );

		// Filter by configured max booking days
		return self::filterTimeframesByMaxBookingDays( $posts );
	}

	/**
	 * Filters posts for days, that are active in timeframe.
	 * Why? Because you can define days for your timeframe. Here's the point where we make sure, that only these days
	 *      are taken into account.
	 *
	 * @param array $posts
	 * @param string|null $date string format: YYYY-mm-dd
	 *
	 * @return array
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	private static function filterTimeframesByConfiguredDays( array $posts, ?string $date ): array {
		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			if ( $date ) {
				$posts = array_filter( $posts, function ( $post ) use ( $date ) {
					if ( $weekdays = get_post_meta( $post->ID, 'weekdays', true ) ) {
						$day = date( 'N', strtotime( $date ) );

						return in_array( $day, $weekdays );
					}

					return true;
				} );
			}

			Plugin::setCacheItem($posts);
			return $posts;
		}
	}

	/**
	 * Filters timeframes from array, which aren't bookable because of the max booking days in
	 * advance setting.
	 *
	 * @param $posts
	 *
	 * @return array
	 */
	private static function filterTimeframesByMaxBookingDays( $posts ): array {
		return array_filter( $posts, function ( $post ) {
			if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType() ) {
				try {
					$timeframe = new \CommonsBooking\Model\Timeframe( $post );

					return $timeframe->isBookable();
				} catch ( Exception $e ) {
					error_log( $e->getMessage() );

					return false;
				}
			}

			return true;
		} );
	}

	/**
	 * Instantiate models for posts.
	 * Why? In some cases we need more than WP_Post methods and for this case we have Models, that enrich WP_Post
	 *      objects with useful additional functions.
	 *
	 * @param $posts
	 *
	 * @throws Exception
	 */
	private static function castPostsToModels( &$posts ) {
		foreach ( $posts as &$post ) {
			// If we have a standard timeframe
			if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType() ) {
				$post = new \CommonsBooking\Model\Timeframe( $post );
			}

			// If it is a booking
			if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Booking::getPostType() ) {
				$post = new \CommonsBooking\Model\Booking( $post );
			}
		}
	}

	/**
	 * Returns timeframe that matches for timestamp based on date AND its time.
	 * Why? Needed for booking creation based on multiple timeframes with different multi slot grids.
	 *
	 * @param int $locationId
	 * @param int $itemId
	 * @param int $timestamp
	 *
	 * @return \CommonsBooking\Model\Timeframe
	 * @throws Exception
	 */
	public static function getByLocationItemTimestamp( int $locationId, int $itemId, int $timestamp ): ?\CommonsBooking\Model\Timeframe {
		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			$time_format        = esc_html(get_option( 'time_format' ));
			$startTimestampTime = date( $time_format, intval( $timestamp ) );
			$endTimestampTime   = date( $time_format, intval( $timestamp ) + 1 );

			$relevantTimeframes = self::getInRange(
				$timestamp,
				$timestamp,
				[ $locationId ],
				[ $itemId ],
				[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
				true
			);

			/** @var \CommonsBooking\Model\Timeframe $timeframe */
			foreach ( $relevantTimeframes as $timeframe ) {
				if (
					date( $time_format, strtotime( $timeframe->getStartTime() ) ) == $startTimestampTime ||
					date( $time_format, strtotime( $timeframe->getEndTime() ) ) == $endTimestampTime
				) {
					Plugin::setCacheItem( $timeframe );

					return $timeframe;
				}
			}
		}

		return null;
	}

	/**
	 * Returns timeframes in explicit timerange.
	 * Why? We often need timeframes for a specific timerange. For example in the calendar the default range is
	 *      three months. Another example is the table view.
	 *
	 * @param array $locations
	 * @param array $items
	 * @param array $types
	 * @param false $returnAsModel
	 * @param $minTimestamp
	 * @param $maxTimestamp
	 * @param string[] $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getInRange(
		$minTimestamp,
		$maxTimestamp,
		array $locations = [],
		array $items = [],
		array $types = [],
		bool $returnAsModel = false,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		if ( ! count( $types ) ) {
			$types = [
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			];
		}

		$customId = md5( serialize( $types ) );
		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			$posts = [];

			// Get Post-IDs considering types, items and locations
			$postIds = self::getPostIdsByType( $types, $items, $locations );

			if ( $postIds && count( $postIds ) ) {
				$posts = self::getPostsByBaseParams(
					null,
					$minTimestamp,
					$maxTimestamp,
					$postIds,
					$postStatus
				);
			}

			// if returnAsModel == TRUE the result is a timeframe model instead of a WordPress object
			if ( $returnAsModel ) {
				foreach ( $posts as &$post ) {
					$post = new \CommonsBooking\Model\Timeframe( $post );
				}
			}

			Plugin::setCacheItem( $posts, $customId );

			return $posts;
		}
	}

}
