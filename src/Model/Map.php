<?php

namespace CommonsBooking\Model;

use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Wordpress\CustomPostType\Location;
use Exception;

/**
 * This class does the heavy lifting for the map shortcode
 * Code style differs because it has been taken from the fLotte Map shortcode plugin
 *
 */
class Map extends CustomPost {

	/**
	 * fetches locations and if available the connected items.
	 * Will only include items and locations when either no categories are set or the item / location has a category that is in the map config
	 * attaches start / enddate of the bookable timeframe to the item
	 * Fetches location & item metadata to be displayed on the map
	 *
	 * @param $mapItemTerms array of term ids
	 *
	 * @return array with postIDs as keys for an array with location data relevant for this map
	 * @throws Exception
	 */
	public function get_locations( array $mapItemTerms ): array {
		$locations = [];

		$show_location_contact = $this->getMeta( 'show_location_contact' );

		$preset_categories          = $this->getMeta( 'cb_items_preset_categories' );
		$preset_location_categories = $this->getMeta( 'cb_locations_preset_categories' );


		$args = [
			'post_type'      => Location::$postType,
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'          => 'geo_longitude',
					'meta_compare' => 'EXISTS',
				],
			],
		];

		$locationObjects = \CommonsBooking\Repository\Location::get(
			$args,
			true
		);

		/** @var \CommonsBooking\Model\Location $post */
		foreach ( $locationObjects as $post ) {
			$location_meta = get_post_meta( $post->ID, null, true );

			//set serialized empty array if not set
			//THIS FUNCTIONALITY IS DEPRECATED, closing days were a feature of 0.9.X
			$closed_days = isset( $location_meta['commons-booking_location_closeddays'] ) ? $location_meta['commons-booking_location_closeddays'][0] : 'a:0:{}';

			$items = [];

			/**
			 * filters out not preset location categories, if location categories are set
			 */

			if ( $preset_location_categories ) {
				if ( ! has_term( $preset_location_categories, 'cb_locations_category', $post->ID ) ) {
					continue; //skip to next location in loop
				}
			}

			foreach ( Item::getByLocation( $post->ID, true ) as $item ) {

				$item_terms = wp_get_post_terms(
					$item->ID,
					\CommonsBooking\Wordpress\CustomPostType\Item::$postType . 's_category'
				);
				if ( is_array( $item_terms ) && count( $item_terms ) ) {
					$item_terms = array_map(
						function ( $item ) {
							return $item->term_id;
						},
						$item_terms
					);
				}

				/**
				 * If current item has a category, that isn't in map config, we'll skip it.
				 */
				if ( count( $mapItemTerms ) && count( $item_terms ) && ! count( array_intersect( $item_terms, $mapItemTerms ) ) ) {
					continue;
				}

				/**
				 * Filter items by preset item categories
				 */

				if ( $preset_categories ) {
					//check if preset category is in items
					if ( ! has_term( $preset_categories, 'cb_items_category', $item->ID ) ) {
						continue; //skip to next item in loop
					}
				}


				$timeframesData = [];
				$timeframes     = Timeframe::getBookableForCurrentUser(
					[ $post->ID ],
					[ $item->ID ],
					null,
					true
				);

				/** @var \CommonsBooking\Model\Timeframe $timeframe */
				foreach ( $timeframes as $timeframe ) {
					$startDate        = date( 'Y-m-d', $timeframe->getStartDate() );
					$endDate          = $timeframe->getEndDate() ?: date( 'Y-m-d', strtotime( '2999-01-01' ) );
					$timeframesData[] = [
						'date_start' => $startDate,
						'date_end'   => $endDate
					];
				}

				$thumbnailID = get_post_thumbnail_id( $item->ID );
				//this thumbnail is kept for backwards compatibility
				$thumbnail = wp_get_attachment_image_url( $thumbnailID, 'thumbnail' );
				$images    = [
					'thumbnail' => wp_get_attachment_image_src( $thumbnailID, 'thumbnail' ),
					'medium'    => wp_get_attachment_image_src( $thumbnailID, 'medium' ),
					'large'     => wp_get_attachment_image_src( $thumbnailID, 'large' ),
					'full'      => wp_get_attachment_image_src( $thumbnailID, 'full' ),
				];
				$items[]   = [
					'id'         => $item->ID,
					'name'       => $item->post_title,
					'short_desc' => has_excerpt( $item->ID ) ? wp_strip_all_tags( get_the_excerpt( $item->ID ) ) : "",
					'status'     => $item->post_status,
					'terms'      => $item_terms,
					'link'       => add_query_arg( 'cb-location', $post->ID, get_permalink( $item->ID ) ),
					'thumbnail'  => $thumbnail ?: null,
					'images'     => $images,
					'timeframes' => $timeframesData
				];
			}

			if ( count( $items ) ) {
				$locations[ $post->ID ] = [
					'lat'           => (float) $location_meta['geo_latitude'][0],
					'lon'           => (float) $location_meta['geo_longitude'][0],
					'location_name' => $post->post_title,
					'location_link' => get_permalink( $post->ID ),
					'closed_days'   => unserialize( $closed_days ),
					'address'       => [
						'street' => $location_meta[ COMMONSBOOKING_METABOX_PREFIX . 'location_street' ][0],
						'city'   => $location_meta[ COMMONSBOOKING_METABOX_PREFIX . 'location_city' ][0],
						'zip'    => $location_meta[ COMMONSBOOKING_METABOX_PREFIX . 'location_postcode' ][0],
					],
					'items'         => $items,
				];

				if ( $show_location_contact ) {
					$locations[ $post->ID ]['contact'] = $location_meta[ COMMONSBOOKING_METABOX_PREFIX . 'location_contact' ][0];
				}
			}
		}

		return $locations;
	}

	/**
	 * recursive clean up of location data entries
	 *
	 * @param $value
	 * @param $linebreak_replacement
	 *
	 * @return mixed|string|string[]|null
	 */
	public static function cleanup_location_data_entry( $value, $linebreak_replacement ) {

		if ( is_string( $value ) ) {
			$value = wp_strip_all_tags( $value ); //strip all tags
			$value = preg_replace( '/(\r\n)|\n|\r/', $linebreak_replacement, $value ); //replace linebreaks
		}

		if ( is_array( $value ) ) {
			foreach ( $value as &$child_value ) {
				//recursive call
				$child_value = self::cleanup_location_data_entry( $child_value, $linebreak_replacement );
			}
		}

		return $value;
	}

	/**
	 * clean up the location data
	 *
	 * @param $locations
	 * @param $linebreak_replacement
	 *
	 * @return mixed
	 */
	public static function cleanup_location_data( $locations, $linebreak_replacement ) {
		foreach ( $locations as &$location ) {
			$location = self::cleanup_location_data_entry( $location, $linebreak_replacement );
		}

		return $locations;
	}

	/**
	 * basic check if the given string is valid JSON
	 **/
	public static function is_json( $string ): bool {
		json_decode( $string );

		return ( json_last_error() == JSON_ERROR_NONE );
	}
}