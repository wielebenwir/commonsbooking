<?php

/*
Plugin Name:  Commons Booking Map
Plugin URI:   https://github.com/flotte-berlin/commons-booking-map
Description:  Ein Plugin in Ergänzung zu Commons Booking, das die Einbindung einer Karte von verfügbaren Artikeln erlaubt
Version:      0.9.2
Author:       poilu
Author URI:   https://github.com/poilu
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

use CommonsBooking\Map\LocationMapAdmin;
use CommonsBooking\Map\Map;
use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Map\MapShortcode;

load_plugin_textdomain('commons-booking-map', false, COMMONSBOOKING_MAP_LANG_PATH);

$cb_map_settings = new \CommonsBooking\Map\MapSettings();
$cb_map_settings->prepare_settings();
add_filter("plugin_action_links_".plugin_basename(__FILE__), array($cb_map_settings, 'add_settings_link'));

add_action('init', array(Map::class, 'register_cb_map_post_type'));
add_action('save_post_cb_map', array(MapAdmin::class, 'validate_options'), 10, 3);
add_action('add_meta_boxes_cb_map', array(MapAdmin::class, 'add_meta_boxes'));


add_action('wp_ajax_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_nopriv_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));
add_action('wp_ajax_nopriv_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));
add_shortcode('cb_map', array(MapShortcode::class, 'execute'));

add_action('wp_ajax_cb_map_import_source_test', array(Map::class, 'handle_location_import_test'));
add_action('wp_ajax_nopriv_cb_map_import_source_test', array(Map::class, 'handle_location_import_test'));

add_action('wp_ajax_cb_map_location_import_of_map', array(Map::class, 'handle_location_import_of_map'));
add_action('wp_ajax_nopriv_cb_map_location_import_of_map', array(Map::class, 'handle_location_import_of_map'));

//location map administration
$cb_map_admin = new LocationMapAdmin();
add_action('plugins_loaded', array($cb_map_admin, 'load_location_map_admin'));

add_action('cb_map_import', array(Map::class, 'import_all_locations'));
register_activation_hook(__FILE__, array(Map::class, 'activate'));
register_deactivation_hook(__FILE__, array(Map::class, 'deactivate'));

if ($cb_map_settings->get_option('booking_page_link_replacement')) {
    add_action('wp_enqueue_scripts', array(Map::class, 'replace_map_link_target'), 11);
}
