<?php

namespace CommonsBooking\Map;

class MapShortcode extends BaseShortcode {
	protected function create_container($cb_map_id, $attrs, $options, $content) {
		$map_height = MapAdmin::get_option( $cb_map_id, 'map_height' );
		return '<div id="cb-map-' . esc_attr( $cb_map_id ) . '" class="cb-wrapper cb-leaflet-map" style="width: 100%; height: ' . esc_attr( $map_height ) . 'px;"></div>';
	}
	protected function parse_attributes( $atts ) {
		return shortcode_atts(array('id' => 0), $atts);
	}

	protected function inject_script($cb_map_id) {
		wp_add_inline_script( 'cb-map-shortcode',
			"jQuery(document).ready(function ($) {
            var cb_map = new CB_Map();
            cb_map.settings = " . wp_json_encode( MapData::get_settings( $cb_map_id ) ) . ";
            cb_map.translation = " . wp_json_encode( $this->get_translation( $cb_map_id ) ) . ";
            cb_map.init_filters($);
            cb_map.init_map();
        });" );
		wp_enqueue_style( 'cb-map-shortcode' );
		wp_enqueue_script( 'cb-map-shortcode' );
	}

	/**
	 * get the translations for the frontend
	 **/
	private function get_translation( $cb_map_id ): array {
		$label_location_opening_hours   = MapAdmin::get_option( $cb_map_id, 'label_location_opening_hours' );
		$label_location_contact         = MapAdmin::get_option( $cb_map_id, 'label_location_contact' );
		$custom_no_locations_message    = MapAdmin::get_option( $cb_map_id, 'custom_no_locations_message' );
		$custom_filterbutton_label      = MapAdmin::get_option( $cb_map_id, 'custom_filterbutton_label' );
		$label_item_availability_filter = MapAdmin::get_option( $cb_map_id, 'label_item_availability_filter' );
		$label_item_category_filter     = MapAdmin::get_option( $cb_map_id, 'label_item_category_filter' );
		$label_location_distance_filter = MapAdmin::get_option( $cb_map_id, 'label_location_distance_filter' );

		return [
			'OPENING_HOURS'          => strlen( $label_location_opening_hours ) > 0 ? $label_location_opening_hours : esc_html__( 'opening hours', 'commonsbooking' ),
			'CONTACT'                => strlen( $label_location_contact ) > 0 ? $label_location_contact : esc_html__( 'contact', 'commonsbooking' ),
			'FROM'                   => esc_html__( 'from', 'commonsbooking' ),
			'UNTIL'                  => esc_html__( 'until', 'commonsbooking' ),
			'AT_LEAST'               => esc_html__( 'for at least', 'commonsbooking' ),
			'DAYS'                   => esc_html__( 'day(s)', 'commonsbooking' ),
			'NO_LOCATIONS_MESSAGE'   => strlen( $custom_no_locations_message ) > 0 ? $custom_no_locations_message : esc_html__( 'Sorry, no locations found.', 'commonsbooking' ),
			'FILTER'                 => strlen( $custom_filterbutton_label ) > 0 ? $custom_filterbutton_label : esc_html__( 'filter', 'commonsbooking' ),
			'AVAILABILITY'           => strlen( $label_item_availability_filter ) > 0 ? $label_item_availability_filter : esc_html__( 'availability', 'commonsbooking' ),
			'CATEGORIES'             => strlen( $label_item_category_filter ) > 0 ? $label_item_category_filter : esc_html__( 'categories', 'commonsbooking' ),
			'DISTANCE'               => strlen( $label_location_distance_filter ) > 0 ? $label_location_distance_filter : esc_html__( 'distance', 'commonsbooking' ),
			'ADDRESS'                => esc_html__( 'address', 'commonsbooking' ),
			'GEO_SEARCH_ERROR'       => esc_html__( 'Sorry, an error occured during your request. Please try again later.', 'commonsbooking' ),
			'GEO_SEARCH_UNAVAILABLE' => esc_html__( 'The service is currently not available. Please try again later.', 'commonsbooking' ),
			'COMING_SOON'            => esc_html__( 'comming soon', 'commonsbooking' ),
		];
	}

	public static function geo_search() {
		if ( isset( $_POST['query'] ) && $_POST['cb_map_id'] ) {

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

			$options = MapAdmin::get_options( sanitize_text_field( $_POST['cb_map_id'] ), true );

			if ( $options['address_search_bounds_left_bottom_lat'] && $options['address_search_bounds_left_bottom_lon'] && $options['address_search_bounds_right_top_lat'] && $options['address_search_bounds_right_top_lon'] ) {
				$params['bounded'] = 1;
				//viewbox - lon1, lat1, lon2, lat2: 12.856779316446545, 52.379790828551016, 13.948545673868422, 52.79694936237738
				$params['viewbox'] = $options['address_search_bounds_left_bottom_lon'] . ',' . $options['address_search_bounds_left_bottom_lat'] . ',' . $options['address_search_bounds_right_top_lon'] . ',' . $options['address_search_bounds_right_top_lat'];
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
			} else {
				wp_send_json_error( [ 'error' => 2 ], 400 );

				wp_die();
			}
		} else {
			wp_send_json_error( [ 'error' => 3 ], 400 );

			wp_die();
		}


		if ( $post->post_status == 'publish' ) {
			$settings           = self::get_settings( $cb_map_id );
			$default_date_start = $settings['filter_availability']['date_min'];
			$default_date_end   = $settings['filter_availability']['date_max'];
			$itemTerms          = self::getItemCategoryTerms( $settings );
			$locations          = Map::get_locations( $cb_map_id, $itemTerms );

			//create availabilities
			$show_item_availability        = MapAdmin::get_option( $cb_map_id, 'show_item_availability' );
			$show_item_availability_filter = MapAdmin::get_option( $cb_map_id, 'show_item_availability_filter' );

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
}
