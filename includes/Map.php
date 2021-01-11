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
use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Map\MapSettings;
use CommonsBooking\Map\MapShortcode;

load_plugin_textdomain('commons-booking-map', false, COMMONSBOOKING_MAP_LANG_PATH);

$cb_map_settings = new MapSettings();
$cb_map_settings->prepare_settings();
add_filter("plugin_action_links_".plugin_basename(__FILE__), array($cb_map_settings, 'add_settings_link'));


add_action('save_post_cb_map', array(MapAdmin::class, 'validate_options'), 10, 3);
add_action('add_meta_boxes_cb_map', array(MapAdmin::class, 'add_meta_boxes'));

add_action('wp_ajax_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_nopriv_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));
add_action('wp_ajax_nopriv_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));
add_shortcode('cb_map', array(MapShortcode::class, 'execute'));
