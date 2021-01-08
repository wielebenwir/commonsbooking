<?php

namespace CommonsBooking\Map;

class LocationMapAdmin
{

    /**
     * load the location administration map
     */
    public function load_location_map_admin()
    {

        $cb_path = cb_map\get_active_plugin_directory('commons-booking.php');

        if ($cb_path) {
            $cmb_path = '../'.$cb_path.'/admin/includes/CMB2/init.php';
            require_once(CB_MAP_PATH.$cmb_path);

            // render map
            add_action('cmb2_render_cb_map', array($this, 'render_cb_map'), 10, 5);

            // sanitize the field
            /*
            add_filter( 'cmb2_sanitize_text_number', 'sm_cmb2_sanitize_text_number', 10, 2 );
            function sm_cmb2_sanitize_text_number( $null, $new ) {
              $new = preg_replace( "/[^0-9]/", "", $new );

              return $new;
            }*/

            add_filter('cmb2_meta_boxes', array($this, 'add_metabox'));
        }
    }

    /**
     * load needed assets for the map that provides fine tuning of the location's position
     **/
    public function render_cb_map($field, $escaped_value, $object_id, $object_type, $field_type_object)
    {
        //map
        wp_enqueue_style('cb_map_leaflet_css', CB_MAP_ASSETS_URL.'leaflet/leaflet.css');
        wp_enqueue_script('cb_map_leaflet_js', CB_MAP_ASSETS_URL.'leaflet/leaflet-src.js');

        echo '<div id="cb_positioning_map" style="width: 100%; height: 400px;"></div>';
        $script_path = CB_MAP_ASSETS_URL.'js/cb-map-positioning.js';
        echo '<script src="'.$script_path.'"></script>';

        //map defaults
        $options  = CB_Map_Admin::get_options();
        $defaults = [
            'latitude'  => $options['lat_start'],
            'longitude' => $options['lon_start'],
        ];
        echo '<script>cb_map_positioning.defaults = '.json_encode($defaults).';</script>';
    }

    /**
     * add a metabox for location's positioning map
     **/
    public function add_metabox(array $meta_boxes)
    {

        $meta_boxes['cb_locations_map'] = array(
            'id'           => 'cb_locations_map',
            'title'        => cb_map\__('MAP_POSITIONING', 'commons-booking-map', 'Map Positioning'),
            'object_types' => array('cb_locations'), // Post type
            'context'      => 'normal',
            'priority'     => 'low',
            'show_names'   => true, // Show field names on the left
            'fields'       => array(
                array(
                    'id'      => 'cb-map'.'_latitude',
                    'name'    => cb_map\__('LATITUDE', 'commons-booking-map', 'Latitude'),
                    'type'    => 'text_small',
                    'default' => '',
                ),
                array(
                    'id'      => 'cb-map'.'_longitude',
                    'name'    => cb_map\__('LONGITUDE', 'commons-booking-map', 'Longitude'),
                    'type'    => 'text_small',
                    'default' => '',
                ),
                array(
                    'id'      => 'cb-map'.'_position',
                    'name'    => cb_map\__('POSITION', 'commons-booking-map', 'Position'),
                    'type'    => 'cb_map',
                    'default' => '',
                ),
            ),
        );

        return $meta_boxes;
    }
}

?>
