<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

class Restriction extends PostRepository {

	/**
	 * @throws \Exception
	 * @return \CommonsBooking\Model\Restriction[]
	 */
	public static function get(
		array $locations = [],
		array $items = [],
		?string $date = null,
		bool $returnAsModel = false,
		$minTimestamp = null,
		array $postStatus = [ 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
	): array {
		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			global $wpdb;
			$table_postmeta = $wpdb->prefix . 'postmeta';
			$table_posts    = $wpdb->prefix . 'posts';
			$dateQuery = '';

			// Filter by date
			if ( $date && ! $minTimestamp ) {
				$dateQuery = "
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


			// Filter only from a specific start date.
			// Rep-End must be > Min Date (0:00)
			if ( $minTimestamp ) {
				$dateQuery = "
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

			// Complete query
			$query = "
                SELECT DISTINCT pm1.* from $table_posts pm1
                " . $dateQuery . "
                WHERE
                    pm1.post_type = '" . \CommonsBooking\Wordpress\CustomPostType\Restriction::getPostType() . "' AND
                    pm1.post_status IN ('" . implode( "','", $postStatus ) . "')
            ";

			$posts = $wpdb->get_results( $query, ARRAY_N );

			if ( $posts && count( $posts ) ) {
				// Get posts from result
				foreach ( $posts as &$post ) {
					$post = get_post( $post[0] );
				}

				// If there are locations or items to be filtered, we iterate through
				// query result because wp_query is to slow for meta-querying them.
				if ( count( $locations ) || count( $items ) ) {
					$posts = array_filter( $posts, function ( $post ) use ( $locations, $items ) {
						$isActive = get_post_meta( $post->ID, \CommonsBooking\Model\Restriction::META_STATE, true ) == 1;
						$location = intval( get_post_meta( $post->ID, \CommonsBooking\Model\Restriction::META_LOCATION_ID, true ) );
						$location = $location == 0 ?? false;
						$item     = intval( get_post_meta( $post->ID, \CommonsBooking\Model\Restriction::META_ITEM_ID, true ) );
						$item = $item == 0 ?? false;

						return
							$isActive
							&& (
								( ! $location && ! $item ) ||
								( ! $location && in_array( $item, $items ) ) ||
								( in_array( $location, $locations ) && ! $item ) ||
								( ! count( $locations ) && in_array( $item, $items ) ) ||
								( in_array( $location, $locations ) && ! count( $items ) ) ||
								( in_array( $location, $locations ) && in_array( $item, $items ) )
							)
						;
					} );
				}

				// if returnAsModel == TRUE the result is a timeframe model instead of a wordpress object
				if ( $returnAsModel ) {
					foreach ( $posts as &$post ) {
						$post = new \CommonsBooking\Model\Restriction( $post );
					}
				}
			}

			Plugin::setCacheItem( $posts );
			return $posts ?: [];
		}
	}

}