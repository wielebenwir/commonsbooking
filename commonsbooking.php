<?php
defined('ABSPATH') or die("Thanks for visting");

/**
 * Plugin Name:  CommonsBooking
 * Plugin URI: ~
 * Description: ~
 * Version: 0.1.0
 * License: GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  commonsbooking
 */

define('CB_TEXTDOMAIN', 'commonsbooking');
define('CB_VERSION', '0.0');
define('CB_MENU_SLUG', 'cb-menu');
define('CB_PLUGIN_SLUG', 'commonsbooking');
define('CB_PLUGIN_DIR', plugin_dir_path(__FILE__));

function commonsbooking_admin()
{
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'Resources/assets/admin/css/admin.css');

    #wp_enqueue_script('cb-scripts-jquery', 'https://cdn.jsdelivr.net/jquery/latest/jquery.min.js', array(), '1.0.0', true);
    wp_enqueue_script('cb-scripts-admin', plugin_dir_url(__FILE__) . 'Resources/assets/admin/js/admin.js', array());
}
add_action('admin_enqueue_scripts', 'commonsbooking_admin');

function commonsbooking_public()
{
    wp_enqueue_style('cb-styles-public', plugin_dir_url(__FILE__) . 'Resources/assets/public/css/public.css');
    wp_enqueue_style('cb-styles-daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css');

    wp_enqueue_script('cb-scripts-jquery', 'https://cdn.jsdelivr.net/jquery/latest/jquery.min.js', array(), '1.0.0', true);
    wp_enqueue_script('cb-scripts-moment', 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', array(), '1.0.0', true);
    wp_enqueue_script('cb-scripts-daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array(), '1.0.0', true);

    // commented out until changes are implemented in extension
    // wp_enqueue_script('cb-scripts-litepicker', 'https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js', array(), '1.0.0', true);
    wp_enqueue_script('cb-scripts-public', plugin_dir_url(__FILE__) . 'Resources/assets/public/js/public.js', array());

    wp_localize_script('cb-scripts-public', 'cb_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('calendar_data')));
}

add_action('wp_enqueue_scripts', 'commonsbooking_public');
add_action('wp_ajax_calendar_data', array(\CommonsBooking\View\Location::class, 'get_calendar_data'));

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/cmb2/cmb2/init.php';


$cbPlugin = new \CommonsBooking\Plugin();
$cbPlugin->init();
