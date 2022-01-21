<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

class Restriction extends PostRepository {

	/**
	 * Returns query to filter by date.
	 *
	 * @param $date
	 *
	 * @return string
	 */
	private static function getDateQuery( $date ) {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';

		return "
	                INNER JOIN $table_postmeta pm4 ON
	                    pm4.post_id = pm1.id AND
	                    pm4.meta_key = '" . \CommonsBooking\Model\Restriction::META_START . "' AND
	                    pm4.meta_value BETWEEN 0 AND " . strtotime( $date . 'T23:59' ) . " 
	                INNER JOIN $table_postmeta pm5 ON
	                    pm5.post_id = pm1.id AND (
	                        (
	                            pm5.meta_key = '" . \CommonsBooking\Model\Restriction::META_END . "' AND
	                            pm5.meta_value BETWEEN " . strtotime( $date ) . " AND 3000000000
	                        ) OR
	                        (
	                            pm1.id not in (
	                                SELECT post_id FROM $table_postmeta 
	                                WHERE 
	                                    meta_key = '" . \CommonsBooking\Model\Restriction::META_END . "'
	                            )
	                        )
	                    )                        
	            ";
	}

	/**
	 * Returns filter to query be mininum timestamp
	 *
	 * @param $minTimestamp
	 *
	 * @return string
	 */
	private static function getMinTimestampQuery( $minTimestamp ) {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';

		return "
                INNER JOIN $table_postmeta pm4 ON
                    pm4.post_id = pm1.id AND (
                        ( 
                            pm4.meta_key = '" . \CommonsBooking\Model\Restriction::META_END . "' AND
                            pm4.meta_value > " . $minTimestamp . "
                        ) OR
                        (
                            pm1.id not in (
                                SELECT post_id FROM $table_postmeta 
                                WHERE
                                    meta_key = '" . \CommonsBooking\Model\Restriction::META_END . "'
                            )
                        )
                    )
            ";
	}

	/**
	 * Returns query to filter only active restrictions.
	 * @return string
	 */
	private static function getActiveQuery() {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';

		return "INNER JOIN $table_postmeta pm2 ON
            pm2.post_id = pm1.id AND (                         
                pm2.meta_key = '" . \CommonsBooking\Model\Restriction::META_STATE . "' AND
                pm2.meta_value = '" . \CommonsBooking\Model\Restriction::STATE_ACTIVE . "'
            )";
	}

	/**
	 * Filters posts by locations and items.
	 *
	 * @param $posts
	 * @param $locations
	 * @param $items
	 *
	 * @return array
	 */
	private static function filterPosts( $posts, $locations, $items ) {
		return array_filter( $posts, function ( $post ) use ( $locations, $items ) {
			// Check if restriction is in relation to item and/or location
			$location               = intval( get_post_meta( $post->ID, \CommonsBooking\Model\Restriction::META_LOCATION_ID, true ) );
			$restrictionHasLocation = $location !== 0;
			$restrictedLocationInLocations = $restrictionHasLocation && in_array( $location, $locations );

			$item               = intval( get_post_meta( $post->ID, \CommonsBooking\Model\Restriction::META_ITEM_ID, true ) );
			$restrictionHasItem = $item !== 0;
			$restrictedItemInItems = $restrictionHasItem && in_array( $item, $items );

			// No item or location for restriction set
			$noLocationNoItem = ( ! $restrictionHasLocation && ! $restrictionHasItem );

			// No location, item matching
			$noLocationItemMatches = (
				!$restrictionHasLocation &&
				$restrictionHasItem &&
				$restrictedItemInItems
			);

			// No item, location matching
			$noItemLocationMatches = (
				! $restrictionHasItem &&
				$restrictionHasLocation &&
				$restrictedLocationInLocations
			);

			// Item and location matching
			$itemAndLocationMatches = (
				$restrictionHasLocation &&
				$restrictedLocationInLocations &&
				$restrictionHasItem &&
				$restrictedItemInItems
			);

			return
				$noLocationNoItem ||
				$noLocationItemMatches ||
				$noItemLocationMatches ||
				$itemAndLocationMatches;
		} );
	}

	/**
	 * Queries posts from db.
	 * @param $date
	 * @param $minTimestamp
	 * @param $postStatus
	 *
	 * @return array|object|null
	 */
	private static function queryPosts( $date, $minTimestamp, $postStatus ) {
		global $wpdb;
		$table_posts = $wpdb->prefix . 'posts';

		$dateQuery = '';

		// Filter only from a specific start date.
		// Rep-End must be > Min Date (0:00)
		if ( $minTimestamp ) {
			$dateQuery = self::getMinTimestampQuery( $minTimestamp );
		} // Filter by date
		elseif ( $date && ! $minTimestamp ) {
			$dateQuery = self::getDateQuery( $date );
		}

		// Complete query
		$query = "
                SELECT DISTINCT pm1.* from $table_posts pm1                
                " . $dateQuery . "
                " . self::getActiveQuery() . "
                WHERE
                    pm1.post_type = '" . \CommonsBooking\Wordpress\CustomPostType\Restriction::getPostType() . "' AND
                    pm1.post_status IN ('" . implode( "','", $postStatus ) . "')
            ";

		return $wpdb->get_results( $query, ARRAY_N );
	}

	/**
	 * Sanitizes array to be better to handle.
	 * @param $posts
	 *
	 * @return mixed
	 */
	private static function sanitizePosts( $posts ) {
		// Get posts from result
		foreach ( $posts as &$post ) {
			$post = get_post( $post[0] );
		}

		return $posts;
	}

	/**
	 * Casts all posts in the array to Restriction objects.
	 * @param $posts
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private static function castPostsToRestrictions( $posts ) {
		foreach ( $posts as &$post ) {
			$post = new \CommonsBooking\Model\Restriction( $post );
		}

		return $posts;
	}

	/**
	 * Returns active restrictions.
	 * @return \CommonsBooking\Model\Restriction[]
	 * @throws \Exception
	 */
	public static function get(
		array $locations = [],
		array $items = [],
		?string $date = null,
		bool $returnAsModel = false,
		$minTimestamp = null,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		$customCacheKey = serialize( $postStatus );

		if ( Plugin::getCacheItem( $customCacheKey ) ) {
			return Plugin::getCacheItem( $customCacheKey );
		} else {

			$posts = self::queryPosts( $date, $minTimestamp, $postStatus );

			if ( $posts && count( $posts ) ) {
				$posts = self::sanitizePosts( $posts );

				// If there are locations or items to be filtered, we iterate through
				// query result because wp_query is to slow for meta-querying them.
				if ( count( $locations ) || count( $items ) ) {
					$posts = self::filterPosts( $posts, $locations, $items );
				}

				// if returnAsModel == TRUE the result is a timeframe model instead of a wordpress object
				if ( $returnAsModel ) {
					$posts = self::castPostsToRestrictions( $posts );
				}
			}

			Plugin::setCacheItem( $posts, $customCacheKey );
			return $posts ?: [];
		}
	}

}