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

require __DIR__ . '/vendor/autoload.php';

function cbDashboard() {
    echo \CommonsBooking\View\Dashboard::render();
}

// Add menu pages
add_action( 'admin_menu', 'cb_menuitems' );
function cb_menuitems() {
    add_menu_page(
        'Commons Booking',
        'Commons Booking',
        'manage_options',
        'cb-dashboard',
        'cbDashboard'
    );

    $cbCustomPostTypes = [
        new \CommonsBooking\PostType\Item(),
        new \CommonsBooking\PostType\Location()
    ];

    /** @var \CommonsBooking\PostType\PostType $cbCustomPostType */
    foreach ($cbCustomPostTypes as $cbCustomPostType) {
        $params = $cbCustomPostType->getMenuParams();
        add_submenu_page(
            $params[0],
            $params[1],
            $params[2],
            $params[3],
            $params[4]
        );
    }

//    remove_menu_page('cb-dashboard');
//    remove_menu_page('cb-item');

}


function register_cb_post_types() {
    $cbCustomPostTypes = [
        new \CommonsBooking\PostType\Item(),
        new \CommonsBooking\PostType\Location()
    ];
    /** @var \CommonsBooking\PostType\PostType $customPostType */
    foreach ($cbCustomPostTypes as $customPostType) {
        register_post_type( $customPostType->getPostType(), $customPostType->getArgs() );
    }
}
add_action('init', 'register_cb_post_types');
