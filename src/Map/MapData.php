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

			//because requests to nominatim are limited (max 1/s), we have to check for timestamp of last one and loop for a while, if needed
			while ( $check_capacity ) {

				if ( $attempts > 10 ) {
					wp_send_json_error( [ 'error' => 5 ], 408 );

					wp_die();
				}

				$attempts ++;

				$last_call_timestamp = commonsbooking_sanitizeHTML( get_option( 'cb_map_last_nominatim_call', 0 ) );
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
				//viewbox - lon1, lat1, lon2, lat2: 12.856779316446545, 52.379790828551016, 13.948545673868422, 52.79694936237738
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
			} else {
				if ( $data['response']['code'] == 200 ) {

					if ( Map::is_json( $data['body'] ) ) {
						wp_send_json( $data['body'] );
					} else {
						wp_send_json_error( [ 'error' => 4 ], 403 );
					}
				} else {
					wp_send_json_error( [ 'error' => 3 ], 404 );
				}
			}
		} else {
			wp_send_json_error( [ 'error' => 1 ], 400 );
		}

		wp_die();
	}

	/**
	 * the ajax request handler for locations
	 **/
	public static function get_locations() {
		//handle local/import map
		if ( isset( $_POST['cb_map_id'] ) ) {
			check_ajax_referer( 'cb_map_locations', 'nonce' );

			$post = get_post( intval( $_POST['cb_map_id'] ) );

			if ( $post && $post->post_type == 'cb_map' ) {
				$cb_map_id = $post->ID;
				$map       = new Map( $cb_map_id );
			} else {
				wp_send_json_error( [ 'error' => 2 ], 400 );

				wp_die();
			}
		} else {
			wp_send_json_error( [ 'error' => 3 ], 400 );

			wp_die();
		}


		if ( $post->post_status == 'publish' ) {
			$map                = new Map( $cb_map_id );
			$settings           = self::get_settings( $cb_map_id );
			$default_date_start = $settings['filter_availability']['date_min'];
			$default_date_end   = $settings['filter_availability']['date_max'];
			$itemTerms          = self::getItemCategoryTerms( $settings );
			$locations          = $map->get_locations( $itemTerms );

			//create availabilities
			$show_item_availability        = $map->getMeta( 'show_item_availability' );
			$show_item_availability_filter = $map->getMeta( 'show_item_availability_filter' );

			if ( $show_item_availability || $show_item_availability_filter ) {
				$locations = MapItemAvailable::create_items_availabilities(
					$locations,
					$default_date_start,
					$default_date_end );
			}

			$locations = array_values( $locations ); //locations to indexed array
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
	 **/
	public static function get_settings( $cb_map_id ): array {
		$map                = new Map( $cb_map_id );
		$date_min           = Wordpress::getUTCDateTime();
		$date_min           = $date_min->format( 'Y-m-d' );
		$max_days_in_future = $map->getMeta( 'availability_max_days_to_show' );
		$date_max           = Wordpress::getUTCDateTime( $date_min . ' + ' . $max_days_in_future . ' days' );
		$date_max           = $date_max->format( 'Y-m-d' );
		$maxdays            = $map->getMeta( 'availability_max_day_count' );

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

		$options = MapAdmin::get_options( $cb_map_id, true );

		$pass_through = [
			'base_map',
			'show_scale',
			'zoom_min',
			'zoom_max',
			'scrollWheelZoom',
			'zoom_start',
			'lat_start',
			'lon_start',
			'marker_map_bounds_initial',
			'marker_map_bounds_filter',
			'max_cluster_radius',
			'marker_tooltip_permanent',
			'show_location_contact',
			'show_location_opening_hours',
			'show_item_availability',
			'show_location_distance_filter',
			'label_location_distance_filter',
			'show_item_availability_filter',
			'label_item_availability_filter',
			'label_item_category_filter',
		];

		foreach ($pass_through as $key) {
			$settings[$key] = $map->getMeta($key);
		}

		if ($map->getMeta('custom_marker_media_id')) {
			$settings['custom_marker_icon'] = [
				'iconUrl'    => wp_get_attachment_url($map->getMeta('custom_marker_media_id')),
				'iconSize'   => [$map->getMeta('marker_icon_width'), $map->getMeta('marker_icon_height')],
				'iconAnchor' => [$map->getMeta('marker_icon_anchor_x'), $map->getMeta('marker_icon_anchor_y')],
			];
		}

		if ($map->getMeta('marker_item_draft_media_id')) {
			$settings['item_draft_marker_icon'] = [
				'iconUrl'    => wp_get_attachment_url($map->getMeta('marker_item_draft_media_id')),
				'iconSize'   => [$map->getMeta('marker_item_draft_icon_width'), $map->getMeta('marker_item_draft_icon_height')],
				'iconAnchor' => [$map->getMeta('marker_item_draft_icon_anchor_x'), $map->getMeta('marker_item_draft_icon_anchor_y')],
			];
		}

		if ($map->getMeta('custom_marker_cluster_media_id')) {
			$settings['marker_cluster_icon'] = [
				'url'  => wp_get_attachment_url($map->getMeta('custom_marker_cluster_media_id')),
				'size' => [
					'width'  => $map->getMeta('marker_cluster_icon_width'),
					'height' => $map->getMeta('marker_cluster_icon_height'),
				],
			];
		}

		//categories are only meant to be shown on local maps
		if ($map->getMeta('cb_items_available_categories')) {
			$settings['filter_cb_item_categories'] = [];
			$current_group_id                      = null;
			foreach ( $map->getMeta('cb_items_available_categories') as $categoryKey => $content ) {
				if ( substr( $categoryKey, 0, 1 ) == 'g' ) {
					$current_group_id                                      = $categoryKey;
					$settings['filter_cb_item_categories'][ $categoryKey ] = [
						'name'     => $content,
						'elements' => [],
					];
				} else {
					$settings['filter_cb_item_categories'][ $current_group_id ]['elements'][] = [
						'cat_id' => $categoryKey,
						'markup' => $content,
					];
				}
			}
		}

		return $settings;
	}
}