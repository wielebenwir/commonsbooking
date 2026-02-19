<?php

namespace CommonsBooking\Map;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\Map;

class MapData {
	public static function geo_search() {
		if ( isset( $_POST['query'] ) && $_POST['cb_map_id'] ) {
			$map = new Map( $_POST['cb_map_id'] );

			$check_capacity = true;
			$attempts       = 0;

			// because requests to nominatim are limited (max 1/s), we have to check for timestamp of last one and loop for a while, if needed
			while ( $check_capacity ) {
				if ( $attempts > 10 ) {
					wp_send_json_error( [ 'error' => 5 ], 408 );
				}

				++$attempts;

				$last_call_timestamp = intval( commonsbooking_sanitizeHTML( get_option( 'cb_map_last_nominatim_call', 0 ) ) );
				$current_timestamp   = time();

				if ( $current_timestamp > $last_call_timestamp + 1 ) {
					$check_capacity = false;
				} else {
					sleep( 1 );
				}
			}

			update_option( 'cb_map_last_nominatim_call', $current_timestamp );

			$params = [
				'q'      => sanitize_text_field( $_POST['query'] ),
				'format' => 'json',
				'limit'  => 1,
			];

			if ( $map->getMeta( 'address_search_bounds_left_bottom_lat' ) && $map->getMeta( 'address_search_bounds_left_bottom_lon' ) && $map->getMeta( 'address_search_bounds_right_top_lat' ) && $map->getMeta( 'address_search_bounds_right_top_lon' ) ) {
				$params['bounded'] = 1;
				// viewbox - lon1, lat1, lon2, lat2: 12.856779316446545, 52.379790828551016, 13.948545673868422, 52.79694936237738
				$params['viewbox'] = $map->getMeta( 'address_search_bounds_left_bottom_lon' ) . ',' . $map->getMeta( 'address_search_bounds_left_bottom_lat' ) . ',' . $map->getMeta( 'address_search_bounds_right_top_lon' ) . ',' . $map->getMeta( 'address_search_bounds_right_top_lat' );
			}

			$url  = 'https://nominatim.openstreetmap.org/search?' . http_build_query( $params );
			$args = [
				'headers' => [
					'Referer' => 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . "{$_SERVER['HTTP_HOST']}",
				],
			];
			$data = wp_safe_remote_get( $url, $args );

			if ( is_wp_error( $data ) ) {
				wp_send_json_error( [ 'error' => 2 ], 404 );
			} elseif ( $data['response']['code'] == 200 ) {
					json_decode( $data['body'] );
					// Check if the json is valid
				if ( json_last_error() == JSON_ERROR_NONE ) {
					wp_send_json( $data['body'] );
				} else {
					wp_send_json_error( [ 'error' => 4 ], 403 );
				}
			} else {
				wp_send_json_error( [ 'error' => 3 ], 404 );
			}
		} else {
			wp_send_json_error( [ 'error' => 1 ], 400 );
		}
	}

	/**
	 * the ajax request handler for locations
	 **/
	public static function get_locations() {
		// handle local/import map
		if ( isset( $_POST['cb_map_id'] ) ) {
			check_ajax_referer( 'cb_map_locations', 'nonce' );

			$post = get_post( intval( $_POST['cb_map_id'] ) );

			if ( $post && $post->post_type == 'cb_map' ) {
				$cb_map_id = $post->ID;
				$map       = new Map( $cb_map_id );
			} else {
				wp_send_json_error( [ 'error' => 2 ], 400 );
			}
		} else {
			wp_send_json_error( [ 'error' => 3 ], 400 );
		}

		if ( $post->post_status == 'publish' ) {
			$map                = new Map( $cb_map_id );
			$settings           = self::get_settings( $cb_map_id );
			$default_date_start = $settings['filter_availability']['date_min'];
			$default_date_end   = $settings['filter_availability']['date_max'];
			$itemTerms          = self::getItemCategoryTerms( $settings );
			$locations          = $map->get_locations( $itemTerms );

			// create availabilities
			$show_item_availability        = $map->getMeta( 'show_item_availability' );
			$show_item_availability_filter = $map->getMeta( 'show_item_availability_filter' );

			if ( $show_item_availability || $show_item_availability_filter ) {
				$locations = MapItemAvailable::create_items_availabilities(
					$locations,
					$default_date_start,
					$default_date_end
				);
			}

			$locations = array_values( $locations ); // locations to indexed array
			$locations = Map::cleanup_location_data( $locations, '<br>' );

			header( 'Content-Type: application/json' );
			echo wp_json_encode( $locations, JSON_UNESCAPED_UNICODE );
		} else {
			wp_send_json_error( [ 'error' => 4 ], 403 );
		}

		wp_die();
	}

	/**
	 * Returns configured item terms
	 *
	 * @return array
	 */
	public static function getItemCategoryTerms( $settings ): array {
		$terms = [];

		foreach ( $settings['filter_cb_item_categories'] as $categoryGroup ) {
			if ( array_key_exists( 'elements', $categoryGroup ) ) {
				foreach ( $categoryGroup['elements'] as $category ) {
					$terms[] = $category['cat_id'];
				}
			}
		}

		return $terms;
	}

	/**
	 * get the settings for the frontend of the map with given id
	 *
	 * @param int|\WP_Post $cb_map_id
	 * @return array
	 * @throws \Exception
	 */
	public static function get_settings($cb_map_id ): array {
		$map                = new Map( $cb_map_id );
		$date_min           = Wordpress::getUTCDateTime();
		$date_min           = $date_min->format( 'Y-m-d' );
		$max_days_in_future = $map->getMeta( 'availability_max_days_to_show' );
		if ( ! is_numeric( $max_days_in_future ) || $max_days_in_future < 1 ) {
			$max_days_in_future = 11;
		}
		$date_max = Wordpress::getUTCDateTime( $date_min . ' + ' . $max_days_in_future . ' days' );
		$date_max = $date_max->format( 'Y-m-d' );
		$maxdays  = $map->getMeta( 'availability_max_day_count' );

		$settings = [
			'data_url'                     => get_site_url( null, '', null ) . '/wp-admin/admin-ajax.php',
			'nonce'                        => wp_create_nonce( 'cb_map_locations' ),
			'custom_marker_icon'           => null,
			'item_draft_marker_icon'       => null,
			'preferred_status_marker_icon' => 'publish',
			'filter_cb_item_categories'    => [],
			'filter_availability'          => [
				'date_min'      => $date_min,
				'date_max'      => $date_max,
				'day_count_max' => $maxdays,
			],
			'cb_map_id'                    => $cb_map_id,
			'locale'                       => str_replace( '_', '-', get_locale() ),
			'asset_path'                   => COMMONSBOOKING_MAP_ASSETS_URL,
		];

		$pass_through = [
			'base_map',
			'zoom_min',
			'zoom_max',
			'zoom_start',
			'lat_start',
			'lon_start',
			'max_cluster_radius',
			'label_location_distance_filter',
			'label_item_availability_filter',
			'label_item_category_filter',
		];

		$pass_through_conditional = [
			'show_scale',
			'scrollWheelZoom',
			'marker_map_bounds_initial',
			'marker_map_bounds_filter',
			'marker_tooltip_permanent',
			'show_location_contact',
			'show_location_opening_hours',
			'show_item_availability',
			'show_location_distance_filter',
			'show_item_availability_filter',
		];

		foreach ( $pass_through as $key ) {
			$meta = $map->getMeta( $key );
			if ( is_numeric( $meta ) ) {
				$meta = floatval( $meta );
			}
			$settings[ $key ] = $meta;
		}

		foreach ( $pass_through_conditional as $key ) {
			$meta = $map->getMeta( $key );
			if ( $meta == 'off' ) {
				$settings[ $key ] = false;
			} else {
				$settings[ $key ] = boolval( $meta );
			}
		}

		if ( $map->getMeta( 'custom_marker_media_id' ) ) {
			$settings['custom_marker_icon'] = [
				'iconUrl'    => wp_get_attachment_url( $map->getMetaInt( 'custom_marker_media_id' ) ),
				'iconSize'   => [
					$map->getMetaInt( 'marker_icon_width' ),
					$map->getMetaInt( 'marker_icon_height' ),
				],
				'iconAnchor' => [
					$map->getMetaInt( 'marker_icon_anchor_x' ),
					$map->getMetaInt( 'marker_icon_anchor_y' ),
				],
			];
		}

		if ( $map->getMeta( 'marker_item_draft_media_id' ) ) {
			$settings['item_draft_marker_icon'] = [
				'iconUrl'    => wp_get_attachment_url( $map->getMetaInt( 'marker_item_draft_media_id' ) ),
				'iconSize'   => [
					$map->getMetaInt( 'marker_item_draft_icon_width' ),
					$map->getMetaInt( 'marker_item_draft_icon_height' ),
				],
				'iconAnchor' => [
					$map->getMetaInt( 'marker_item_draft_icon_anchor_x' ),
					$map->getMetaInt( 'marker_item_draft_icon_anchor_y' ),
				],
			];
		}

		if ( $map->getMeta( 'custom_marker_cluster_media_id' ) ) {
			$settings['marker_cluster_icon'] = [
				'url'  => wp_get_attachment_url( $map->getMetaInt( 'custom_marker_cluster_media_id' ) ),
				'size' => [
					'width'  => $map->getMetaInt( 'marker_cluster_icon_width' ),
					'height' => $map->getMetaInt( 'marker_cluster_icon_height' ),
				],
			];
		}

		// categories are only meant to be shown on local maps
		// TODO: Evaluate if it makes sense to only show them when categories are imported
		if ( $map->getMeta( 'cb_items_available_categories' ) ) {
			$settings['filter_cb_item_categories'] = [];

			$filterGroups = $map->getMeta( 'filtergroups' );
			if ( is_array( $filterGroups ) ) {
				foreach ( $filterGroups as $groupID => $group ) {
					$elements = [];
					foreach ( $group['categories'] as $termID ) {
						$term         = get_term( $termID );
						$customMarkup = get_term_meta( $termID, COMMONSBOOKING_METABOX_PREFIX . 'markup', true );
						$termName     = empty( $customMarkup ) ? $term->name : $customMarkup;

						$elements[] = [
							'cat_id' => intval( $termID ),
							'markup' => $termName,
						];
					}
					$isExclusive                                       = $group['isExclusive'] ?? 'off';
					$settings['filter_cb_item_categories'][ $groupID ] = [
						'name'        => $group['name'] ?? '',
						'elements'    => $elements,
						'isExclusive' => $isExclusive == 'on',
					];
				}
			}
		}
		return $settings;
	}
}
