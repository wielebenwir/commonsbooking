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
}