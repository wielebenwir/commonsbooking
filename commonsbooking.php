<?php
defined('ABSPATH') or die("Thanks for visting");

/**
 * Plugin Name:  CommonsBooking
 * Plugin URI: ~
 * Description: ~
 * Version: 0.0.1
 * License: GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  commonsbooking
 */

define( 'TRANSLATION_CONST', 'commonsbooking');
define( 'CB_MENU_SLUG', 'cb-menu');
define( 'CB_PLUGIN_SLUG', 'commonsbooking');
define( 'COMMONSBOOKING__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

function commonsbooking_admin_style() {
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'Resources/assets/admin/css/admin.css');
}
add_action('admin_enqueue_scripts', 'commonsbooking_admin_style');

function commonsbooking_public_style() {
    wp_enqueue_style('cb-styles-public', plugin_dir_url(__FILE__) . 'Resources/assets/public/css/public.css');
    wp_enqueue_script( 'cb-scripts-public', plugin_dir_url(__FILE__) . 'Resources/assets/public/js/public.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'commonsbooking_public_style' );

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/cmb2/cmb2/init.php';

$cbPlugin = new \CommonsBooking\Plugin();
$cbPlugin->init();
