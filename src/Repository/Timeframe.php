<?php


namespace CommonsBooking\Repository;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\Day;
use CommonsBooking\Plugin;
use Exception;

/*
 * Implements data access to timeframe custom post objects
 *
 * @since 2.9.0 Supports now single and multi selection of items and locations
 */
class Timeframe extends PostRepository {

	/**
	 * Returns only bookable timeframes.
	 *
	 * @param array        $locations
	 * @param array        $items
	 * @param string|null  $date
	 * @param bool         $returnAsModel
	 * @param $minTimestamp
	 * @param array        $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getBookable(
		array $locations = array(),
		array $items = array(),
		?string $date = null,
		bool $returnAsModel = false,
		$minTimestamp = null,
		array $postStatus = array( 'confirmed', 'unconfirmed', 'publish', 'inherit' )
	): array {
		return self::get(
			$locations,
			$items,
			array( \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ),
			$date,
			$returnAsModel,
			$minTimestamp,
			$postStatus
		);
	}

	/**
	 * Returns only bookable timeframes for current user.
	 *
	 * @param array        $locations
	 * @param array        $items
	 * @param string|null  $date
	 * @param bool         $returnAsModel
	 * @param $minTimestamp
	 * @param array        $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getBookableForCurrentUser(
		array $locations = array(),
		array $items = array(),
		?string $date = null,
		bool $returnAsModel = false,
		$minTimestamp = null,
		array $postStatus = array( 'confirmed', 'unconfirmed', 'publish', 'inherit' )
	): array {

		$bookableTimeframes = self::getBookable(
			$locations,
			$items,
			$date,
			$returnAsModel,
			$minTimestamp,
			$postStatus
		);

		$bookableTimeframes = self::filterTimeframesForCurrentUser( $bookableTimeframes );

		return $bookableTimeframes;
	}

	/**
	 * Function to get timeframes with all possible options/params.
	 * Why? We have different types of timeframes and in some cases we need multiple of them.
	 *      In this case we need this function.
	 *      Other functions use this one as base function for more specialized searches.
	 *
	 * TODO: Investigate
	 *       This function is not based on the WP_Query class, probably because of performance reasons.
	 *
	 * @param array       $locations
	 * @param array       $items
	 * @param array       $types
	 * @param string|null $date Date-String in format YYYY-mm-dd
	 * @param bool        $returnAsModel
	 * @param int|null    $minTimestamp
	 * @param string[]    $postStatus
	 *
	 * @return array
	 * @throws Exception
	 * @throws \Psr\Cache\InvalidArgumentException|\Psr\Cache\CacheException
	 */
	public static function get(
		array $locations = array(),
		array $items = array(),
		array $types = array(),
		?string $date = null,
		bool $returnAsModel = false,
		?int $minTimestamp = null,
		array $postStatus = array( 'confirmed', 'unconfirmed', 'publish', 'inherit' )
	): array {
		if ( ! count( $types ) ) {
			$types = array(
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			);
		}

		$customId  = md5( serialize( $types ) );
		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$posts = array();

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

			// if returnAsModel == TRUE the result is a timeframe model instead of a WordPress object
			if ( $returnAsModel ) {
				self::castPostsToModels( $posts );
			}

			Plugin::setCacheItem(
				$posts,
				Wordpress::getTags( $posts, $items, $locations ),
				$customId
			);
			return $posts;
		}
	}

	/**
	 * Will get all timeframes in the database to perform mass operations on (like migrations).
	 *
	 * @param int   $page
	 * @param int   $perPage
	 * @param array $customArgs
	 *
	 * @return \stdClass Properties: array posts, int totalPosts, int totalPages, bool done
	 * @throws Exception
	 */
	public static function getAllPaginated(
		int $page = 1,
		int $perPage = 10,
		array $customArgs = array()
	): \stdClass {
		$args  = array(
			'post_type'      => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType(),
			'paged'          => $page,
			'posts_per_page' => $perPage,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		$args  = array_merge( $args, $customArgs );
		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			self::castPostsToModels( $posts );

			return (object) (
			array(
				'posts'      => $posts,
				'totalPosts' => $query->found_posts,
				'totalPages' => $query->max_num_pages,
				'done'       => $page >= $query->max_num_pages,
			)
			);
		}

		return (object) (
		array(
			'posts'      => array(),
			'totalPosts' => 0,
			'totalPages' => 0,
			'done'       => true,
		)
		);
	}


	/**
	 * Will get the timeframes in a specific range and return them as paginated result.
	 * This will not consider the weekday configuration of the timeframes.j
	 * We need this for the Timeframe Export, so that it does not time out on large datasets.
	 * This function is in general slower than the getInRange function. But it can be used in AJAX requests.
	 *
	 * @param int      $minTimestamp
	 * @param int|null $maxTimestamp
	 * @param int      $page
	 * @param int      $perPage
	 * @param array    $types
	 * @param bool     $asModel
	 * @param array    $customArgs
	 *
	 * @return array An array with the keys 'posts', 'totalPages' and 'done' (bool) to indicate if there are more posts to fetch
	 */
	public static function getInRangePaginated(
		int $minTimestamp,
		int $maxTimestamp = null,
		int $page = 1,
		int $perPage = 10,
		array $types = array(
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
		),
		$postStatus = array( 'confirmed', 'unconfirmed', 'canceled', 'publish', 'inherit' ),
		bool $asModel = false,
		array $customArgs = array()
	): array {
		$args = array(
			'post_type'   => array(
				\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
			),
			// get posts within the range and also posts that do not have a repetition end
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => $maxTimestamp,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
						'value'   => $minTimestamp,
						'compare' => '>=',
						'type'    => 'numeric',
					),
					array(
						'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
						'compare' => 'NOT EXISTS',
					),
				),
				array(
					'key'     => 'type',
					'value'   => $types,
					'compare' => 'IN',
				),
			),
			'post_status'    => $postStatus,
			'paged'          => $page,
			'posts_per_page' => $perPage,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		// Overwrite args with passed custom args
		$args = array_merge( $args, $customArgs );

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			if ( ! isset( $args['fields'] ) || $args['fields'] !== 'ids' ) {
				$posts = array_filter(
					$posts,
					function ( $post ) use ( $args ) {
						return in_array( $post->post_status, $args['post_status'] );
					}
				);
			}

			if ( $asModel ) {
				self::castPostsToModels( $posts );
			}
			return array(
				'posts'      => $posts,
				'totalPages' => $query->max_num_pages,
				'totalPosts' => $query->found_posts,
				'done'       => $page >= $query->max_num_pages,
			);
		}
		return array(
			'posts'      => array(),
			'totalPages' => 0,
			'totalPosts' => 0,
			'done'       => true,
		);
	}

	/**
	 * Returns Post-IDs of timeframes by type(s), item(s), location(s)
	 *
	 * Why? It's because of performance. We use the ids as base set for following filter queries.
	 *
	 * @param array $types the types of timeframes to return, will return default set when not set
	 * @param array $items the items that the timeframes should be applicable to, will return all if not set
	 * @param array $locations the locations that the timeframes should be applicable to, will return all if not set
	 *
	 * @since 2.9.0 Supports now single and multi selection for items and locations
	 *
	 * @return mixed
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getPostIdsByType( array $types = array(), array $items = array(), array $locations = array() ) {

		if ( ! count( $types ) ) {
			$types = array(
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			);
		}

		$customId  = md5( serialize( $types ) );
		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			global $wpdb;
			$table_postmeta = $wpdb->prefix . 'postmeta';

			$items     = array_filter( $items );
			$locations = array_filter( $locations );

			// additional sanitizing. Allow only integer
			$items     = commonsbooking_sanitizeArrayorString( $items, 'intval' );
			$locations = commonsbooking_sanitizeArrayorString( $locations, 'intval' );
			$types     = commonsbooking_sanitizeArrayorString( $types, 'intval' );

			$itemQuery = '';
			if ( count( $items ) > 0 ) {
				$itemQuery = self::getEntityQuery(
					'pm2',
					$table_postmeta,
					$items,
					\CommonsBooking\Model\Timeframe::META_ITEM_ID,
					\CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST
				);
			}

			$locationQuery = '';
			if ( count( $locations ) > 0 ) {
				$locationQuery = self::getEntityQuery(
					'pm3',
					$table_postmeta,
					$locations,
					\CommonsBooking\Model\Timeframe::META_LOCATION_ID,
					\CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST
				);
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
	                pm1.meta_value IN (" . implode( ',', $types ) . ')
            ';

			// Run query
			$postIds = $wpdb->get_results( $query, ARRAY_N );

			// Get Post-IDs
			foreach ( $postIds as &$post ) {
				$post = $post[0];
			}

			// Get Posts
			$posts = array_map(
				function ( $post ) {
					return get_post( $post );
				},
				$postIds
			);

			Plugin::setCacheItem(
				$postIds,
				Wordpress::getTags( $posts, $items, $locations ),
				$customId
			);

			return $postIds;
		}
	}

	/**
	 * Returns entity query as join statement, which considers single and multi selection.
	 *
	 * @since 2.9.0 Supports now single and multi selection for items and locations
	 *
	 * @return string join statement
	 */
	private static function getEntityQuery( string $joinAlias, string $table_postmeta, array $entities, string $singleEntityKey, string $multiEntityKey ): string {
		$locationQueryParts = array();

		// Single select
		$singleLocationQuery  = "(
		                        $joinAlias.meta_key = '" . $singleEntityKey . "' AND
		                        $joinAlias.meta_value IN (" . implode( ',', $entities ) . ')
	                        )';
		$locationQueryParts[] = $singleLocationQuery;

		// Multi select
		$multiLocationQueries = array();
		foreach ( $entities as $entityId ) {
			$multiLocationQueries[] = "$joinAlias.meta_value LIKE '%:\"$entityId\";%'";
		}
		$multiLocationQuery   = "(
					$joinAlias.meta_key = '" . $multiEntityKey . "' AND
					(" . implode( ' OR ', $multiLocationQueries ) . ') 
				)';
		$locationQueryParts[] = $multiLocationQuery;

		return "INNER JOIN $table_postmeta $joinAlias ON
                    $joinAlias.post_id = pm1.post_id AND
                    (" . implode( ' OR ', $locationQueryParts ) . ')';
	}

	/**
	 * Queries for posts within $postIds and filters them by $date and/or $minTimestamp and $postStatus.
	 * Why? This kind of filtering is needed nearly everywhere in commonsbooking.
	 *
	 * @param string|null $date
	 * @param int|null    $minTimestamp
	 * @param int|null    $maxTimestamp
	 * @param array       $postIds
	 * @param array       $postStatus
	 *
	 * @return array
	 */
	private static function getPostsByBaseParams( ?string $date, ?int $minTimestamp, ?int $maxTimestamp, array $postIds, array $postStatus ): array {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			global $wpdb;
			$table_postmeta = $wpdb->prefix . 'postmeta';
			$table_posts    = $wpdb->prefix . 'posts';
			$dateQuery      = '';

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
                    " . $dateQuery . '
                    WHERE
                        pm1.id in (' . implode( ',', $postIds ) . ") AND
                        pm1.post_type IN ('" . implode( "','", \CommonsBooking\Wordpress\CustomPostType\Timeframe::getSimilarPostTypes() ) . "') AND
                        pm1.post_status IN ('" . implode( "','", $postStatus ) . "')
                ";

			$posts = $wpdb->get_results( $query );
			$posts = Wordpress::flattenWpdbResult( $posts );

			Plugin::setCacheItem(
				$posts,
				Wordpress::getTags( $posts, $postIds )
			);

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
                pm4.meta_key = %s AND
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
			\CommonsBooking\Model\Timeframe::REPETITION_START,
			strtotime( $date . 'T23:59' ),
			strtotime( $date )
		);
	}

	/**
	 * Returns query to get posts which end after $minTimestamp.
	 *
	 * @param string $table_postmeta
	 * @param int    $minTimestamp
	 *
	 * @return string
	 */
	private static function getFilterFromDateQuery( string $table_postmeta, int $minTimestamp ): string {
		global $wpdb;
		$minTimestamp = Helper::getLastFullDayTimestamp( $minTimestamp );

		return $wpdb->prepare(
			"INNER JOIN $table_postmeta pm4 ON
	            pm4.post_id = pm1.id AND (
	                (
	                    pm4.meta_key = '" . \CommonsBooking\Model\Timeframe::REPETITION_END . "' AND
	                    pm4.meta_value >= %d
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
	 * @param int    $minTimestamp
	 * @param int    $maxTimestamp
	 *
	 * @return string
	 */
	private static function getTimerangeQuery( string $table_postmeta, int $minTimestamp, int $maxTimestamp ): string {
		global $wpdb;
		return $wpdb->prepare(
			"INNER JOIN $table_postmeta pm4 ON
	            pm4.post_id = pm1.id AND (
	                pm4.meta_key = %s AND
	                pm4.meta_value <= %d
	            )
	        INNER JOIN $table_postmeta pm5 ON
	            pm5.post_id = pm1.id AND (
	                (
	                    pm5.meta_key = %s AND
	                    pm5.meta_value >= %d
	                ) OR (
	                    NOT EXISTS (
	                        SELECT * FROM $table_postmeta
	                        WHERE
	                            meta_key = %s AND
	                            post_id = pm5.post_id
	                    )
	                )
	            )
	        ",
			\CommonsBooking\Model\Timeframe::REPETITION_START,
			$maxTimestamp,
			\CommonsBooking\Model\Timeframe::REPETITION_END,
			$minTimestamp,
			\CommonsBooking\Model\Timeframe::REPETITION_END
		);
	}

	/**
	 * Wrapper function for all filters.
	 *
	 * @param array       $posts
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
	 * @param array       $posts
	 * @param string|null $date string format: YYYY-mm-dd
	 *
	 * @return array
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	private static function filterTimeframesByConfiguredDays( array $posts, ?string $date ): array {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			if ( $date ) {
				$posts = array_filter(
					$posts,
					function ( $post ) use ( $date ) {
						try {
							$timeframe = new \CommonsBooking\Model\Timeframe( $post );
							$day       = new Day( $date );

							return $day->isInTimeframe( $timeframe );
						} catch ( Exception $e ) {
							// this was also default behaviour before #802 (before #802 the function would just check the weekly repetition and if it was active on the given day return true)
							// When none were set, it would return true for all days.
							return true;
						}
					}
				);
			}

			Plugin::setCacheItem( $posts, Wordpress::getTags( $posts ) );

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
		return array_filter(
			$posts,
			function ( $post ) {
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
			}
		);
	}

	/**
	 * Filters timeframes from array,
	 * removes timeframes which are not bookable by current user
	 *
	 * @param $posts
	 *
	 * @return array
	 */
	private static function filterTimeframesForCurrentUser( $posts ): array {
		return array_filter(
			$posts,
			function ( $post ) {
				try {
					return commonsbooking_isCurrentUserAllowedToBook( $post->ID );
				} catch ( Exception $e ) {
					error_log( $e->getMessage() );

					return false;
				}
			}
		);
	}

	/**
	 * Will filter out all timeframes that are not in the given timerange.
	 *
	 * @param \CommonsBooking\Model\Timeframe[] $timeframes
	 * @param int                               $startTimestamp
	 * @param int                               $endTimestamp
	 *
	 * @return \CommonsBooking\Model\Timeframe[]
	 * @throws Exception
	 */
	public static function filterTimeframesForTimerange( array $timeframes, int $startTimestamp, int $endTimestamp ): array {
		return array_filter(
			$timeframes,
			function ( $timeframe ) use ( $startTimestamp, $endTimestamp ) {
				// filter out anything in the future
				if ( $timeframe->getStartDate() > $endTimestamp ) {
					return false;
				}
				// always include infinite timeframes
				if ( ! $timeframe->getEndDate() ) {
					return true;
				}
				// filter out anything in the past
				if ( $timeframe->getEndDate() < $startTimestamp ) {
					return false;
				}

				return true;
			}
		);
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
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$time_format        = esc_html( get_option( 'time_format' ) );
			$startTimestampTime = date( $time_format, $timestamp );
			$endTimestampTime   = date( $time_format, $timestamp + 1 );

			$relevantTimeframes = self::get(
				array( $locationId ),
				array( $itemId ),
				array( \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ),
				date( 'Y-m-d', $timestamp ),
				true
			);

			/** @var \CommonsBooking\Model\Timeframe $timeframe */
			foreach ( $relevantTimeframes as $timeframe ) {
				if (
					date( $time_format, strtotime( $timeframe->getStartTime() ) ) == $startTimestampTime ||
					date( $time_format, strtotime( $timeframe->getEndTime() ) ) == $endTimestampTime
				) {
					Plugin::setCacheItem( $timeframe, array( $timeframe->ID, $itemId, $locationId ) );

					return $timeframe;
				}
			}
		}

		return null;
	}

	/**
	 * Returns timeframes in explicit timerange. Does not consider weekday configurations!!
	 * Why? We often need timeframes for a specific timerange. For example in the calendar the default range is
	 *      three months. Another example is the table view.
	 *
	 * @param $minTimestamp
	 * @param $maxTimestamp
	 * @param array        $locations
	 * @param array        $items
	 * @param array        $types
	 * @param false        $returnAsModel
	 * @param string[]     $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getInRange(
		$minTimestamp,
		$maxTimestamp,
		array $locations = array(),
		array $items = array(),
		array $types = array(),
		bool $returnAsModel = false,
		array $postStatus = array( 'confirmed', 'unconfirmed', 'publish', 'inherit' )
	): array {
		if ( ! count( $types ) ) {
			$types = array(
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			);
		}

		$customId  = md5( serialize( $types ) );
		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$posts = array();

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

			Plugin::setCacheItem(
				$posts,
				Wordpress::getTags( $posts, $locations, $items ),
				$customId
			);

			return $posts;
		}
	}

	/**
	 * Returns timeframes in explicit timerange that are bookable by the current user.
	 *
	 * @param $minTimestamp
	 * @param $maxTimestamp
	 * @param array        $locations
	 * @param array        $items
	 * @param array        $types
	 * @param false        $returnAsModel
	 * @param string[]     $postStatus
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getInRangeForCurrentUser(
		$minTimestamp,
		$maxTimestamp,
		array $locations = array(),
		array $items = array(),
		array $types = array(),
		bool $returnAsModel = false,
		array $postStatus = array( 'confirmed', 'unconfirmed', 'publish', 'inherit' )
	): array {
		$bookableTimeframes = self::getInRange( $minTimestamp, $maxTimestamp, $locations, $items, $types, $returnAsModel, $postStatus );

		$bookableTimeframes = self::filterTimeframesForCurrentUser( $bookableTimeframes );
		return $bookableTimeframes;
	}
}
