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


    public static function renderGeoRefreshButton() {

        ?>
        <div class="cmb-row cmb-type-text ">
        <div class="cmb-th">
            <label><?php echo esc_html__( 'Set / Update GPS Coordinates', 'commonsbooking' ); ?></label>
        </div>
        <div class="cmb-td">
            <button type="button" id="get_gps" class="button button-secondary" onclick="cb_map_positioning.search()">
                <?php echo esc_html__( 'Set / update GPS coordinates from address', 'commonsbooking' ); ?>
            </button>
            <p><?php echo commonsbooking_sanitizeHTML( __('Click this button to automatically set the GPS coordinates based on the given address and set the marker on the map.<br> <strong>Save or update this location after setting the gps data.</strong>', 'commonsbooking' ) ); ?></p>
            <div id="nogpsresult" style="display: none; color: red"><?php 
                echo commonsbooking_sanitizeHTML( __('<strong>No GPS data could be found for the address entered</strong>. <br>Please check if the address is written correctly. <br>Alternatively, you can enter the GPS data manually into the corresponding fields.', 'commonsbooking' ) );
                ?></div>
        </div>
    </div>

    <?php 
    ;

    }

}
