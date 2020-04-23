<?php
defined('ABSPATH') or die("Thanks for visting");

/**
 * Plugin Name:  Commons Booking
 * Plugin URI: ~
 * Description: ~
 * Version: 0.0.1
 * License: GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  commonsbooking
 */

define('TRANSLATION_CONST', 'commonsbooking');
define( 'CB_MENU_SLUG', 'cb-menu');
define( 'COMMONSBOOKING__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

function commonsbooking_admin_style() {
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'Resources/assets/admin/css/admin.css');
}
add_action('admin_enqueue_scripts', 'commonsbooking_admin_style');

require __DIR__ . '/vendor/autoload.php';

$cbPlugin = new \CommonsBooking\Plugin();
$cbPlugin->init();
