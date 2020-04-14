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

$cbPlugin = new \CommonsBooking\Plugin();
$cbPlugin->init();
$cbPlugin->initTables();
