<?php

namespace CommonsBooking\Map;

use CommonsBooking\View\Map;

class LocationMapAdmin {

	/**
	 * load the location administration map
	 */
	public function load_location_map_admin() {
		if ( COMMONSBOOKING_PLUGIN_DIR ) {
			// render map
			add_action( 'cmb2_render_cb_map', array( Map::class, 'render_cb_map' ), 10, 5 );
		}
	}
}
