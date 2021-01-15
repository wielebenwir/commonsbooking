<?php

/**
 * Plugin Name:         CommonsBooking
 * Version:             2.2.16
 * Requires at least:   5.2
 * Requires PHP:        7.0
 * Plugin URI:          https://commonsbooking.org
 * Description:         A wordpress plugin for management and booking of common goods.
 * Author:              wielebenwir e.V.
 * Author URI:          https://wielebenwir.de/
 * Domain Path:         /languages
 * Text Domain:         commonsbooking
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 */

use CommonsBooking\Map\MapShortcode;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Wordpress\Options\AdminOptions;

defined('ABSPATH') or die("Thanks for visting");

define('COMMONSBOOKING_VERSION', '2.2.16');
define('COMMONSBOOKING_PLUGIN_SLUG', 'commonsbooking');
define('COMMONSBOOKING_MENU_SLUG', COMMONSBOOKING_PLUGIN_SLUG . '-menu');
define('COMMONSBOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COMMONSBOOKING_PLUGIN_FILE', __FILE__);
define('COMMONSBOOKING_METABOX_PREFIX', '_cb_'); //Start with an underscore to hide fields from custom fields list

define( 'COMMONSBOOKING_MAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'COMMONSBOOKING_MAP_ASSETS_URL', plugins_url( 'assets/map/', __FILE__ ));
define( 'COMMONSBOOKING_MAP_LANG_PATH', dirname( plugin_basename( __FILE__ )) . '/languages/' );
define ('COMMONSBOOKING_MAP_PLUGIN_DATA', get_file_data( __FILE__, array('Version' => 'Version'), false));

global $cb_db_version;
$cb_db_version = '1.0';

// @TODO: move all of this to either /Public.php or /Admin.php

function commonsbooking_admin()
{
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__).'assets/admin/css/admin.css');
    wp_enqueue_script('cb-scripts-admin', plugin_dir_url(__FILE__).'assets/admin/js/admin.js', array());
    wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'assets/public/css/themes/jquery/jquery-ui.min.css');
    wp_enqueue_script('jquery-ui-datepicker');

    wp_localize_script(
        'cb-scripts-admin',
        'cb_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('start_migration'),
        )
    );
}

add_action('admin_enqueue_scripts', 'commonsbooking_admin');

function commonsbooking_public()
{
    wp_enqueue_style('cb-styles-public', plugin_dir_url(__FILE__).'assets/public/css/public.css');

    // Template specific styles
    $template = wp_get_theme()->template;
    wp_enqueue_style('cb-styles-public', plugin_dir_url(__FILE__).'assets/public/css/themes/'.$template.'.css');

    wp_enqueue_script('jquery');

    wp_enqueue_style(
        'cb-styles-daterangepicker',
        plugin_dir_url(__FILE__) . 'assets/public/css/themes/daterangepicker/daterangepicker.css'
    );

//    wp_enqueue_script(
//        'cb-scripts-jquery',
//        plugin_dir_url(__FILE__) . 'assets/public/js/vendor/jquery.min.js',
//        array(),
//        '1.0.0',
//        true
//    );

    // Moment.js
    wp_enqueue_script(
        'cb-scripts-moment',
        plugin_dir_url(__FILE__) . 'assets/public/js/vendor/moment.min.js',
        array(),
        '1.0.0',
        true
    );

    // Daterangepicker
    wp_enqueue_script(
        'cb-scripts-daterangepicker',
        plugin_dir_url(__FILE__) . 'assets/public/js/vendor/daterangepicker.min.js',
        array(),
        '1.0.0',
        true
    );

    if (WP_DEBUG) {
        wp_enqueue_script('cb-scripts-public', plugin_dir_url(__FILE__).'assets/public/js/public.js', array());
    } else {
        wp_enqueue_script('cb-scripts-public', plugin_dir_url(__FILE__).'assets/public/js/public.min.js', array());
    }

    wp_localize_script(
        'cb-scripts-public',
        'cb_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendar_data'),
        )
    );
}

add_action('wp_enqueue_scripts', 'commonsbooking_public');

// Calendar data ajax
add_action('wp_ajax_calendar_data', array(\CommonsBooking\View\Location::class, 'getCalendarData'));
add_action('wp_ajax_nopriv_calendar_data', array(\CommonsBooking\View\Location::class, 'getCalendarData'));
if (is_admin()) {
    add_action('wp_ajax_start_migration', array(\CommonsBooking\Migration\Migration::class, 'migrateAll'));
}

// Map ajax
add_action('wp_ajax_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_nopriv_cb_map_locations', array(MapShortcode::class, 'get_locations'));
add_action('wp_ajax_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));
add_action('wp_ajax_nopriv_cb_map_geo_search', array(MapShortcode::class, 'geo_search'));

// should be loaded via add_action, but wasnt working in admin menu
load_plugin_textdomain('commonsbooking', false, basename(dirname(__FILE__)).'/languages/');

function commonsbooking_query_vars($qvars)
{
    $qvars[] = 'location';
    $qvars[] = 'item';
    $qvars[] = 'type';

    return $qvars;
}

add_filter('query_vars', 'commonsbooking_query_vars');

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/vendor/cmb2/cmb2/init.php';
require __DIR__.'/vendor/mustardBees/cmb-field-select2/cmb-field-select2.php';

// removed redirect because we link to booking-single-notallowd.php (defined in )
// add_action('template_redirect', 'commonsbooking_timeframe_redirect');

// Shows Errors in Backend
add_action('admin_notices', array(Plugin::class, 'renderError') );

/**
 * commonsbooking_sanitizeHTML
 * Filters text content and strips out disallowed HTML.
 *
 * @param  mixed $string
 * @param  mixed $textdomain
 * @return void
 */
function commonsbooking_sanitizeHTML($string)
{

    global $allowedposttags;

    $allowed_atts = array(
        'align'      => array(),
        'class'      => array(),
        'type'       => array(),
        'id'         => array(),
        'dir'        => array(),
        'lang'       => array(),
        'style'      => array(),
        'xml:lang'   => array(),
        'src'        => array(),
        'alt'        => array(),
        'href'       => array(),
        'rel'        => array(),
        'rev'        => array(),
        'target'     => array(),
        'novalidate' => array(),
        'type'       => array(),
        'value'      => array(),
        'name'       => array(),
        'tabindex'   => array(),
        'action'     => array(),
        'method'     => array(),
        'for'        => array(),
        'width'      => array(),
        'height'     => array(),
        'data'       => array(),
        'title'      => array(),
    );

    $allowedposttags['form']     = $allowed_atts;
    $allowedposttags['label']    = $allowed_atts;
    $allowedposttags['input']    = $allowed_atts;
    $allowedposttags['textarea'] = $allowed_atts;
    $allowedposttags['iframe']   = $allowed_atts;
    $allowedposttags['script']   = $allowed_atts;
    $allowedposttags['style']    = $allowed_atts;
    $allowedposttags['strong']   = $allowed_atts;
    $allowedposttags['small']    = $allowed_atts;
    $allowedposttags['table']    = $allowed_atts;
    $allowedposttags['span']     = $allowed_atts;
    $allowedposttags['abbr']     = $allowed_atts;
    $allowedposttags['code']     = $allowed_atts;
    $allowedposttags['pre']      = $allowed_atts;
    $allowedposttags['div']      = $allowed_atts;
    $allowedposttags['img']      = $allowed_atts;
    $allowedposttags['h1']       = $allowed_atts;
    $allowedposttags['h2']       = $allowed_atts;
    $allowedposttags['h3']       = $allowed_atts;
    $allowedposttags['h4']       = $allowed_atts;
    $allowedposttags['h5']       = $allowed_atts;
    $allowedposttags['h6']       = $allowed_atts;
    $allowedposttags['ol']       = $allowed_atts;
    $allowedposttags['ul']       = $allowed_atts;
    $allowedposttags['li']       = $allowed_atts;
    $allowedposttags['em']       = $allowed_atts;
    $allowedposttags['hr']       = $allowed_atts;
    $allowedposttags['br']       = $allowed_atts;
    $allowedposttags['tr']       = $allowed_atts;
    $allowedposttags['td']       = $allowed_atts;
    $allowedposttags['p']        = $allowed_atts;
    $allowedposttags['a']        = $allowed_atts;
    $allowedposttags['b']        = $allowed_atts;
    $allowedposttags['i']        = $allowed_atts;

    return wp_kses( $string, $allowedposttags );
}

/**
 * Recursive sanitation for text or array
 *
 * @param $array_or_string (array|string)
 * @since  0.1
 * @return mixed
 */
function commonsbooking_sanitizeArrayorString($array_or_string) {
    if( is_string($array_or_string) ){
        $array_or_string = sanitize_text_field($array_or_string);
    }elseif( is_array($array_or_string) ){
        foreach ( $array_or_string as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = commonsbooking_sanitizeArrayorString($value);
            }
            else {
                $value = commonsbooking_sanitizeArrayorString( $value );
            }
        }
    }

    return $array_or_string;
}

// Initialize booking codes table
register_activation_hook(__FILE__, array(\CommonsBooking\Repository\BookingCodes::class, 'initBookingCodesTable'));

// Ad new cron-Interval
function commonsbooking_cron_interval($schedules)
{
    $schedules['ten_minutes'] = array(
        'display'  => 'Every 10 Minutes',
        'interval' => 600,
    );
    return $schedules;
}
add_filter('cron_schedules', 'commonsbooking_cron_interval');

// Removes all uncofirmed bookings older than 10 minutes
function commonsbooking_cleanupBookings()
{
    $args = array(
        'post_type'   => Timeframe::$postType,
        'post_status' => 'unconfirmed',
        'meta_key'    => 'type',
        'meta_value'  => Timeframe::BOOKING_ID,
        'date_query'  => array(
            'before' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
        ),
        'nopaging'    => true,
    );

    $query = new \WP_Query($args);
    if ($query->have_posts()) {
        foreach ($query->get_posts() as $post) {
            if ($post->post_status !== 'unconfirmed') {
                continue;
            }
            wp_delete_post($post->ID);
        }
    }
}
add_action('cb_cron_hook', 'commonsbooking_cleanupBookings');
if ( ! wp_next_scheduled('cb_cron_hook')) {
    wp_schedule_event(time(), 'ten_minutes', 'cb_cron_hook');
}

// Remove schedule on module deactivation
register_deactivation_hook( __FILE__, 'commonsbooking_cron_deactivate' );
function commonsbooking_cron_deactivate() {
    $timestamp = wp_next_scheduled( 'cb_cron_hook' );
    wp_unschedule_event( $timestamp, 'cb_cron_hook' );
}

$cbPlugin = new Plugin();
$cbPlugin->init();
$cbPlugin->initRoutes();
$cbPlugin->initBookingcodes();
