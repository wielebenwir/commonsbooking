<?php

/**
 * Plugin Name:         CommonsBooking
 * Version:             2.2.6
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

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

defined('ABSPATH') or die("Thanks for visting");

define('COMMONSBOOKING_VERSION', '2.2.6');
define('COMMONSBOOKING_PLUGIN_SLUG', 'commonsbooking');
define('COMMONSBOOKING_MENU_SLUG', COMMONSBOOKING_PLUGIN_SLUG . '-menu');
define('COMMONSBOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COMMONSBOOKING_METABOX_PREFIX', '_cb_'); //Start with an underscore to hide fields from custom fields list

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

    wp_enqueue_style(
        'cb-styles-daterangepicker',
        plugin_dir_url(__FILE__) . 'assets/public/css/themes/daterangepicker/daterangepicker.css'
    );

    wp_enqueue_script(
        'cb-scripts-jquery',
        plugin_dir_url(__FILE__) . 'assets/public/js/vendor/jquery.min.js',
        array(),
        '1.0.0',
        true
    );

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

add_action('wp_ajax_calendar_data', array(\CommonsBooking\View\Location::class, 'getCalendarData'));
add_action('wp_ajax_nopriv_calendar_data', array(\CommonsBooking\View\Location::class, 'getCalendarData'));
if (is_admin()) {
    add_action('wp_ajax_start_migration', array(\CommonsBooking\Migration\Migration::class, 'migrateAll'));
}

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

require __DIR__.'/src/Repository/CB1UserFields.php'; //@TODO: import with Autoload

/**
 * Checks if current user is allowed to edit custom post.
 *
 * @param $post
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToEdit($post)
{
    $current_user = wp_get_current_user();
    $isAuthor     = intval($current_user->ID) == intval($post->post_author);
    $isAdmin      = false;
    if (in_array('administrator', (array)$current_user->roles)) {
        $isAdmin = true;
    }

    // Check if it is the main query and one of our custom post types
    if ( ! $isAdmin && ! $isAuthor) {
        $admins = [];

        // Get allowed admins for timeframe listing
        if ($post->post_type == Timeframe::$postType) {
            // Get assigned location
            $locationId       = get_post_meta($post->ID, 'location-id', true);
            $locationAdminIds = get_post_meta($locationId, '_'.Location::$postType.'_admins', true);
            if (is_string($locationAdminIds)) {
                if(strlen($locationAdminIds) > 0) {
                    $locationAdminIds = [$locationAdminIds];
                } else {
                    $locationAdminIds = [];
                }
            }
            $locationAdminIds[] = get_post_field('post_author', $locationId);

            // Get assigned item
            $itemId       = get_post_meta($post->ID, 'item-id', true);
            $itemAdminIds = get_post_meta($itemId, '_'.Item::$postType.'_admins', true);
            if (is_string($itemAdminIds)) {
                if(strlen($itemAdminIds) > 0) {
                    $itemAdminIds = [$itemAdminIds];
                } else {
                    $itemAdminIds = [];
                }
            }
            $itemAdminIds[] = get_post_field('post_author', $itemId);

            if (
                is_array($locationAdminIds) && count($locationAdminIds) &&
                is_array($itemAdminIds) && count($itemAdminIds)
            ) {
                $admins = array_merge($locationAdminIds, $itemAdminIds);
            }
        }

        // Get allowed admins for Location / Item Listing
        if (in_array(
            $post->post_type,
            [
                Location::$postType,
                Item::$postType,
            ]
        )
        ) {
            // post-related admins (returns string if single result and array if multiple results)
            $admins = get_post_meta($post->ID, '_'.$post->post_type.'_admins', true);
        }

        if (
            (is_string($admins) && $current_user->ID != $admins) ||
            is_array($admins) && ! in_array($current_user->ID, $admins)
        ) {
            return false;
        }
    }

    return true;
}

/**
 * Validates if current user is allowed to edit current post in admin.
 *
 * @param $current_screen
 */
function commonsbooking_validate_user_on_edit($current_screen)
{
    if ($current_screen->base == "post" && in_array($current_screen->id, Plugin::getCustomPostTypesLabels())) {
        if (array_key_exists('action', $_GET) && $_GET['action'] == 'edit') {
            $post = get_post($_GET['post']);
            if ( ! commonsbooking_isCurrentUserAllowedToEdit($post)) {
                die('Access denied');
            };
        }
    }
}

add_action('current_screen', 'commonsbooking_validate_user_on_edit', 10, 1);

/**
 * Applies listing restriction for item and location admins.
 */
add_filter(
    'the_posts',
    function ($posts, $query) {
        if (is_admin() && array_key_exists('post_type', $query->query)) {
            // Post type of current list
            $postType = $query->query['post_type'];

            $current_user = wp_get_current_user();
            $isAdmin      = false;
            if (in_array('administrator', (array)$current_user->roles)) {
                $isAdmin = true;
            }

            // Check if it is the main query and one of our custom post types
            if ( ! $isAdmin && $query->is_main_query() && in_array($postType, Plugin::getCustomPostTypesLabels())) {
                foreach ($posts as $key => $post) {
                    if ( ! commonsbooking_isCurrentUserAllowedToEdit($post)) {
                        unset($posts[$key]);
                    }
                }
            }
        }

        return $posts;
    },
    10,
    2
);

// Redirect to startpage if user is not allowed to edit timeframe
function commonsbooking_timeframe_redirect()
{
    global $post;
    if (
        $post &&
        $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType &&
        (
            ( ! current_user_can('administrator') && get_current_user_id() != $post->post_author) ||
            ! is_user_logged_in()
        )
    ) {
        wp_redirect(home_url('/'));
        exit;
    }
}

add_action('template_redirect', 'commonsbooking_timeframe_redirect');

// Shows Errors in Backend
add_action('admin_notices', array(Plugin::class, 'renderError'));

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
    $allowed_html = [
        'a'      => [
            'href'  => [],
            'title' => [],
        ],
        'br'     => [],
        'em'     => [],
        'strong' => [],
        'p' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
    ];

    return wp_kses( $string, $allowed_html );
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
