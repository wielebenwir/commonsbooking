<?php 

namespace CommonsBooking\Map;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Map;

class LocationMapAdmin {

/**
* load the location administration map
*/
public function load_location_map_admin() {

  $cb_path = COMMONSBOOKING_PLUGIN_DIR;

  if($cb_path) {
    //$cmb_path = '../' . $cb_path . '/vendor/cmb2/cmb2/init.php';
    //require_once( COMMONSBOOKING_PLUGIN_DIR. $cmb_path );

    // render map
    add_action( 'cmb2_render_cb_map', array($this, 'render_cb_map'), 10, 5 );

    // sanitize the field
    /*
    add_filter( 'cmb2_sanitize_text_number', 'sm_cmb2_sanitize_text_number', 10, 2 );
    function sm_cmb2_sanitize_text_number( $null, $new ) {
      $new = preg_replace( "/[^0-9]/", "", $new );

      return $new;
    }*/

    add_filter( 'cmb2_meta_boxes', array($this, 'add_metabox'));
  }
}

/**
* load needed assets for the map that provides fine tuning of the location's position
**/
public function render_cb_map( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
  //map
  wp_enqueue_style('cb_map_leaflet_css', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet.css');
  wp_enqueue_script( 'cb_map_leaflet_js', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet-src.js' );

  echo '<div id="cb_positioning_map" style="width: 100%; height: 400px;"></div>';
  $script_path = COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-positioning.js';
  echo '<script src="' . $script_path . '"></script>';

  //map defaults
  $options = MapAdmin::get_options();
  $defaults = [
    'latitude' => $options['lat_start'],
    'longitude' => $options['lon_start'],
  ];
  echo '<script>cb_map_positioning.defaults = ' . json_encode($defaults) . ';</script>';
}

/**
* add a metabox for location's positioning map
**/
public function add_metabox(array $meta_boxes) {

  $meta_boxes['commonsbooking_locations_map'] = array(
    'id'            => 'cb_locations_map',
    'title'         => __( 'MAP_POSITIONING', 'commonsbooking-maps', 'Map Positioning' ),
    'object_types'  => array( 'cb_location' ), // Post type
    'context'       => 'normal',
    'priority'      => 'high',
    'show_names'    => true, // Show field names on the left
    'fields'        => array(
      array(
        'id'        => 'cb-map' . '_latitude',
        'name'      => __( 'LATITUDE', 'commonsbooking-maps', 'Latitude' ),
        'type'      => 'text_small',
          'default'   => ''
      ),
      array(
        'id'        => 'cb-map' . '_longitude',
        'name'      => __( 'LONGITUDE', 'commonsbooking-maps', 'Longitude' ),
        'type'      => 'text_small',
          'default'   => ''
      ),
      array(
        'id'        => 'cb-map' . '_position',
        'name'      => __( 'POSITION', 'commonsbooking-maps', 'Position' ),
        'type'      => 'cb_map',
          'default'   => ''
      )
    )
  );

  return $meta_boxes;
}
}
?>