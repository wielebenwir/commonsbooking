<?php

namespace CommonsBooking\Model;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Wordpress\CustomPostType\Location;
use Exception;

/**
 * This class currently does not have any functionality.
 * It's just a placeholder for the Map post type.
 * Because the map feature was taken from another plugin, it is not yet adapted to the structure of this plugin.
 * This class will be used to adapt the map feature to the structure of this plugin.
 *
 * Currently, it's only referenced by the @see \CommonsBooking\Wordpress\CustomPostType\CustomPostType::getModel() method to use the methods from the CustomPost class.
 */
class Map extends CustomPost {

	/**
	 * get geo data from location metadata
	 *
	 * @param $cb_map_id
	 * @param $mapItemTerms
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_locations( $mapItemTerms ): array {
		$locations = [];

		$show_location_contact       = $this->getMeta( 'show_location_contact' );
		$show_location_opening_hours = $this->getMeta( 'show_location_opening_hours' );

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

			//@TODO: Check field -> we don't have such a field at the moment.
//            if ($show_location_opening_hours) {
//                $locations[$post->ID]['opening_hours'] = $location_meta['commons-booking_location_openinghours'][0];
//            }
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
			$value = preg_replace( '/(\r\n)|\n|\r/', $linebreak_replacement, $value ); //replace linebreaks
			$value = preg_replace( '/<.*(.*?)/', '', $value ); //strip off everything that smell's like HTML
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
	public static function is_json( $string ) {
		json_decode( $string );

		return ( json_last_error() == JSON_ERROR_NONE );
	}

	/**
	 * load all timeframes from db (that end in the future and it's item's status is 'publish')
	 **/
	public function get_timeframes() {
		$timeframes = Timeframe::getBookableForCurrentUser(
			[],
			[],
			false,
			true,
			Helper::getLastFullHourTimestamp()
		);

		/** @var \CommonsBooking\Model\Timeframe $timeframe */
		foreach ( $timeframes as $timeframe ) {
			//TODO #507
			$item     = $timeframe->getItem();
			$location = $timeframe->getLocation();

			if ( $item && $location ) {
				$item_desc = $item->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_info' );
				$thumbnail = get_the_post_thumbnail_url( $item, 'thumbnail' );

				$result[] = [
					'location_id' => $timeframe->getLocationID(),
					'item'        => [
						'id'         => $item->ID,
						'name'       => $item->post_title,
						'short_desc' => $item_desc,
						'link'       => get_permalink( $item ),
						'thumbnail'  => $thumbnail ?: null,
						'status'     => $item->post_status,
					],
					'date_start'  => $timeframe->getStartDate(),
					'date_end'    => $timeframe->getEndDate(),
				];
			}
		}

		return $result;
	}
}