<?php

namespace CommonsBooking\View;

use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Plugin;

/**
 * The Map shortcode. Further logic is found in @see \CommonsBooking\Map\
 */
class Map extends View {

	/**
	 * load needed assets for the map that provides fine tuning of the location's position
	 **/
	public static function render_cb_map( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		// map
		wp_enqueue_style( 'cb-leaflet' );
		wp_enqueue_script( 'cb-leaflet' );

		echo '<div id="cb_positioning_map" style="width: 100%; height: 400px;"></div>';
		wp_enqueue_script( 'cb-map-positioning_js', COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-positioning.js' );

		// map defaults
		$options  = MapAdmin::get_options();
		$defaults = array(
			'latitude'  => $options['lat_start'],
			'longitude' => $options['lon_start'],
		);
		wp_add_inline_script( 'cb-map-positioning_js', 'cb_map_positioning.defaults =' . wp_json_encode( $defaults ) );
	}

	public static function renderGeoRefreshButton() {
		echo '<div class="cmb-row cmb-type-text ">
			<div class="cmb-th">
				<label>' . esc_html__( 'Set / Update GPS Coordinates', 'commonsbooking' ) . '</label>
			</div>
			<div class="cmb-td">
				<button type="button" id="get_gps" class="button button-secondary" onclick="cb_map_positioning.search()">' .
					esc_html__( 'Set / update GPS coordinates from address', 'commonsbooking' ) .
				'</button>
				<p>' .
					commonsbooking_sanitizeHTML( __( 'Click this button to automatically set the GPS coordinates based on the given address and set the marker on the map.<br> <strong>Save or update this location after setting the gps data.</strong>', 'commonsbooking' ) ) .
				'</p>
				<div id="nogpsresult" style="display: none; color: red">' .
					commonsbooking_sanitizeHTML( __( '<strong>No GPS data could be found for the address entered</strong>. <br>Please check if the address is written correctly. <br>Alternatively, you can enter the GPS data manually into the corresponding fields.', 'commonsbooking' ) ) .
				'</div>
			</div>
		</div>';
	}
}
