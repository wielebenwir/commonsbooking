<?php
// Shows Errors in Backend
use CommonsBooking\Plugin;

add_action( 'admin_notices', array( Plugin::class, 'renderError' ) );

// Initialize booking codes table
register_activation_hook( COMMONSBOOKING_PLUGIN_FILE, array( Plugin::class, 'activation' ) );

// Do action upon module deactivation
register_deactivation_hook( COMMONSBOOKING_PLUGIN_FILE, array( Plugin::class, 'deactivation' ) );


$cbPlugin = new Plugin();
$cbPlugin->init();
$cbPlugin->initRoutes();
$cbPlugin->initBookingcodes();