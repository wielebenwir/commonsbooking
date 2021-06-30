<?php

namespace CommonsBooking\Map;

class LocationMapAdmin {

	/**
	 * load the location administration map
	 */
	public function load_location_map_admin() {
		if ( COMMONSBOOKING_PLUGIN_DIR ) {
			// render map
			add_action( 'cmb2_render_cb_map', array( $this, 'render_cb_map' ), 10, 5 );
		}
	}

	/**
	 * load needed assets for the map that provides fine tuning of the location's position
	 **/
	public function render_cb_map( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		//map
		wp_enqueue_style( 'cb_map_leaflet_css', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet.css' );
		wp_enqueue_script( 'cb_map_leaflet_js', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet-src.js' );

		echo '<div id="cb_positioning_map" style="width: 100%; height: 400px;"></div>';
		$script_path = COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-positioning.js';
		echo '<script src="' . $script_path . '"></script>';

		//map defaults
		$options  = MapAdmin::get_options();
		$defaults = [
			'latitude'  => $options['lat_start'],
			'longitude' => $options['lon_start'],
		];
		echo '<script>cb_map_positioning.defaults = ' . json_encode( $defaults ) . ';</script>';
	}

}
