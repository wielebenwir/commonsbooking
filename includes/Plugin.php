<?php
// Shows Errors in Backend
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\TimeframeExport;

add_action( 'admin_notices', array( Plugin::class, 'renderError' ) );

// run activation tasks -> doesn't work by require/include
register_activation_hook( __FILE__, array( Plugin::class, 'activation' ) );

// Ad new cron-Interval
function commonsbooking_cron_interval( $schedules ) {
	$schedules['ten_minutes']    = array(
		'display'  => 'Every 10 Minutes',
		'interval' => 600,
	);
	$schedules['five_minutes']   = array(
		'display'  => 'Every 5 Minutes',
		'interval' => 300,
	);
	$schedules['thirty_minutes'] = array(
		'display'  => 'Every 30 Minutes',
		'interval' => 1800,
	);

	return $schedules;
}

add_filter( 'cron_schedules', 'commonsbooking_cron_interval' );

// Removes all unconfirmed bookings older than 10 minutes
function commonsbooking_cleanupBookings() {
	$args = array(
		'post_type'   => Timeframe::$postType,
		'post_status' => 'unconfirmed',
		'meta_key'    => 'type',
		'meta_value'  => Timeframe::BOOKING_ID,
		'date_query'  => array(
			'before' => '-10 minutes',
		),
		'nopaging'    => true,
	);

	$query = new WP_Query( $args );
	if ( $query->have_posts() ) {
		foreach ( $query->get_posts() as $post ) {
			if ( $post->post_status !== 'unconfirmed' ) {
				continue;
			}
			wp_delete_post( $post->ID );
		}
	}
}

add_action( 'cb_cron_hook', 'commonsbooking_cleanupBookings' );
if ( ! wp_next_scheduled( 'cb_cron_hook' ) ) {
	wp_schedule_event( time(), 'ten_minutes', 'cb_cron_hook' );
}

// Add cronjob for csv timeframe export
$cronExport = Settings::getOption( 'commonsbooking_options_export', 'export-cron' );
if ( $cronExport == 'on' ) {
	$exportPath     = Settings::getOption( 'commonsbooking_options_export', 'export-filepath' );
	$exportInterval = Settings::getOption( 'commonsbooking_options_export', 'export-interval' );

	$cbCronHook = 'cb_cron_export';
	add_action( $cbCronHook, function () use ( $exportPath ) {
		TimeframeExport::exportCsv( $exportPath );
	} );

	if ( ! wp_next_scheduled( $cbCronHook ) ) {
		wp_schedule_event( time(), $exportInterval, $cbCronHook );
	}
}

// Remove schedule on module deactivation
register_deactivation_hook( __FILE__, 'commonsbooking_cron_deactivate' );
function commonsbooking_cron_deactivate() {
	$cbCronHooks = [
		'cb_cron_hook',
		'cb_cron_export'
	];

	foreach ( $cbCronHooks as $cbCronHook ) {
		$timestamp = wp_next_scheduled( $cbCronHook );
		wp_unschedule_event( $timestamp, $cbCronHook );
	}
}

/**
 * writes messages to error_log file
 *
 * @param mixed $log can be a string, array or object
 * @param bool $backtrace if set true the file-path and line of the calling file will be added to the error message
 *
 * @return void
 */
function commmonsbooking_write_log( $log, $backtrace = true ) {

	if ( $backtrace ) {
		$bt   = debug_backtrace();
		$file = $bt[0]['file'];
		$line = $bt[0]['line'];
		$log  = $file . ':' . $line . ' ' . $log;
	}

	if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
	} else {
		error_log( $log );
	}
}

$cbPlugin = new Plugin();
$cbPlugin->init();
$cbPlugin->initRoutes();
$cbPlugin->initBookingcodes();
