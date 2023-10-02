<?php

namespace CommonsBooking\Map;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Wordpress\CustomPostType\Map;
use DateTime;

class MapShortcode {

	/**
	 * the shortcode handler - load all the needed assets and render the map container
	 **/
	public static function execute( $atts ): string {

		$a = shortcode_atts( array(
			'id' => 0,
		), $atts );

		if ( (int) $a['id'] ) {
			$post = get_post( $a['id'] );

			if ( $post && $post->post_type == 'cb_map' ) {
				$cb_map_id = $post->ID;

				if ( $post->post_status == 'publish' ) {

					//jquery
					//TODO: This is likely enqueueing jquery twice. The other enqueue happens in @see commonsbooking_public() . A better solution would be to fix load order.
					wp_enqueue_script( 'jquery' );

					//leaflet
					wp_enqueue_style( 'cb_map_leaflet_css', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet.css' );
					wp_enqueue_script( 'cb_map_leaflet_js', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet.js' );

					//leaflet markercluster plugin
					wp_enqueue_style( 'cb_map_leaflet_markercluster_css',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-markercluster/MarkerCluster.css' );
					wp_enqueue_style( 'cb_map_leaflet_markercluster_default_css',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-markercluster/MarkerCluster.Default.css' );
					wp_enqueue_script( 'cb_map_leaflet_markercluster_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-markercluster/leaflet.markercluster.js' );

					//leaflet messagebox plugin
					wp_enqueue_style( 'cb_map_leaflet_messagebox_css',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-messagebox/leaflet-messagebox.css' );
					wp_enqueue_script( 'cb_map_leaflet_messagebox_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-messagebox/leaflet-messagebox.js' );

					//leaflet spin & dependencies
					wp_enqueue_style( 'cb_map_spin_css', COMMONSBOOKING_MAP_ASSETS_URL . 'spin-js/spin.css' );
					wp_enqueue_script( 'cb_map_spin_js', COMMONSBOOKING_MAP_ASSETS_URL . 'spin-js/spin.min.js' );
					wp_enqueue_script( 'cb_map_leaflet_spin_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-spin/leaflet.spin.min.js' );

					//leaflet easybutton
					wp_enqueue_style( 'cb_map_leaflet_easybutton_css',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-easybutton/easy-button.css' );
					wp_enqueue_script( 'cb_map_leaflet_easybutton_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-easybutton/easy-button.js' );

					//dashicons
					wp_enqueue_style( 'dashicons' );

					//overscroll
					wp_enqueue_script( 'cb_map_slider_js', COMMONSBOOKING_MAP_ASSETS_URL . 'overscroll/jquery.overscroll.js' );

					//cb map shortcode
					wp_enqueue_style( 'cb_map_shortcode_css',
						COMMONSBOOKING_MAP_ASSETS_URL . 'css/cb-map-shortcode.css?pv=' . COMMONSBOOKING_MAP_PLUGIN_DATA['Version'] );
					wp_register_script( 'cb_map_shortcode_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-shortcode.js?pv=' . COMMONSBOOKING_MAP_PLUGIN_DATA['Version'] );

					wp_register_script( 'MapFilters_js',
						COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-filters.js?pv=' . COMMONSBOOKING_MAP_PLUGIN_DATA['Version'] );
					wp_enqueue_script( 'MapFilters_js' );

					wp_add_inline_script( 'cb_map_shortcode_js',
						"jQuery(document).ready(function ($) {
                        var cb_map = new CB_Map();
                        cb_map.settings = " . wp_json_encode( self::get_settings( $cb_map_id ) ) . ";
                        cb_map.translation = " . wp_json_encode( self::get_translation( $cb_map_id ) ) . ";
                        cb_map.init_filters($);
                        cb_map.init_map();
                    });" );

					wp_enqueue_script( 'cb_map_shortcode_js' );

					$map_height = MapAdmin::get_option( $cb_map_id, 'map_height' );

					return '<div id="cb-map-' . esc_attr( $cb_map_id ) . '" class="cb-wrapper cb-leaflet-map" style="width: 100%; height: ' . esc_attr( $map_height ) . 'px;"></div>';

				} else {
					return '<div>' . esc_html__( 'map is not published', 'commonsbooking' ) . '</div>';
				}
			} else {
				return '<div>' . esc_html__( 'no valid map id provided', 'commonsbooking' ) . '</div>';
			}

		} else {
			return '<div>' . esc_html__( 'no valid map id provided', 'commonsbooking' ) . '</div>';
		}

	}

	/**
	 * get the settings for the frontend of the map with given id
	 **/
	public static function get_settings( $cb_map_id ): array {
		$date_min           = Wordpress::getUTCDateTime();
		$date_min           = $date_min->format( 'Y-m-d' );
		$max_days_in_future = MapAdmin::get_option( $cb_map_id, 'availability_max_days_to_show' );
		$date_max           = Wordpress::getUTCDateTime( $date_min . ' + ' . $max_days_in_future . ' days' );
		$date_max           = $date_max->format( 'Y-m-d' );
		$maxdays            = MapAdmin::get_option( $cb_map_id, 'availability_max_day_count' );

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

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $pass_through ) ) {
				$settings[ $key ] = $value;
			} elseif ( $key == 'custom_marker_media_id' ) {
				if ( $value != null ) {
					$settings['custom_marker_icon'] = [
						'iconUrl'    => wp_get_attachment_url( $options['custom_marker_media_id'] ),
						//'shadowUrl'     => 'leaf-shadow.png',
						'iconSize'   => [ $options['marker_icon_width'], $options['marker_icon_height'] ],
						//[27, 35], // size of the icon
						//'shadowSize'    => [50, 64], // size of the shadow
						'iconAnchor' => [ $options['marker_icon_anchor_x'], $options['marker_icon_anchor_y'] ],
						//[13.5, 0], // point of the icon which will correspond to marker's location
						//'shadowAnchor'  => [4, 62],  // the same for the shadow
						//'popupAnchor'   => [-3, -76] // point from which the popup should open relative to the iconAnchor
					];
				}
			} elseif ( $key == 'marker_item_draft_media_id' ) {
				if ( $value != null ) {
					$settings['item_draft_marker_icon'] = [
						'iconUrl'    => wp_get_attachment_url( $options['marker_item_draft_media_id'] ),
						'iconSize'   => [
							$options['marker_item_draft_icon_width'],
							$options['marker_item_draft_icon_height'],
						], //[27, 35], // size of the icon
						'iconAnchor' => [
							$options['marker_item_draft_icon_anchor_x'],
							$options['marker_item_draft_icon_anchor_y'],
						], //[13.5, 0], // point of the icon which will correspond to marker's location
					];
				}
			} elseif ( $key == 'custom_marker_cluster_media_id' ) {
				if ( $value != null ) {
					$settings['marker_cluster_icon'] = [
						'url'  => wp_get_attachment_url( $options['custom_marker_cluster_media_id'] ),
						'size' => [
							'width'  => $options['marker_cluster_icon_width'],
							'height' => $options['marker_cluster_icon_height'],
						],
					];
				}
			} //categories are only meant to be shown on local maps
			elseif ( $key == 'cb_items_available_categories' ) {
				$settings['filter_cb_item_categories'] = [];
				$current_group_id                      = null;
				foreach ( $options['cb_items_available_categories'] as $categoryKey => $content ) {
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

		}

		return $settings;
	}

	/**
	 * get the translations for the frontend
	 **/
	public static function get_translation( $cb_map_id ): array {
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
