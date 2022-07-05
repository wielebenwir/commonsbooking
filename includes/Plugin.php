<?php
// Shows Errors in Backend
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\TimeframeExport;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

add_action( 'admin_notices', array( Plugin::class, 'renderError' ) );

// Initialize booking codes table
register_activation_hook( COMMONSBOOKING_PLUGIN_FILE, array( Plugin::class, 'activation' ) );

// Remove schedule on module deactivation
register_deactivation_hook(
	COMMONSBOOKING_PLUGIN_FILE,
	array(\CommonsBooking\Service\Scheduler::class, 'unscheduleEvents')
);

// Init scheduled tasks
\CommonsBooking\Service\Scheduler::initHooks();

/**
 * writes messages to error_log file
 * only active if DEBUG_LOG is on
 *
 * @param mixed $log can be a string, array or object
 * @param bool $backtrace if set true the file-path and line of the calling file will be added to the error message
 *
 * @return void
 */
function commonsbooking_write_log( $log, $backtrace = true ) {

    if (WP_DEBUG_LOG != true ) {
        //return;
    }

    if ( is_array( $log ) || is_object( $log ) ) {
		$logmessage = ( print_r( $log, true ) );
	} else {
		$logmessage =  $log ;
	}

	if ( $backtrace ) {
		$bt   = debug_backtrace();
		$file = $bt[0]['file'];
		$line = $bt[0]['line'];
		$logmessage  = $file . ':' . $line . ' ' . $logmessage;
	}

    error_log( $logmessage ) ;

}

$cbPlugin = new Plugin();
$cbPlugin->init();
$cbPlugin->initRoutes();
$cbPlugin->initBookingcodes();