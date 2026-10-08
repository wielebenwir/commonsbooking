<?php

namespace CommonsBooking\Service;

use CommonsBooking\Model\Timeframe;

/**
 * Creates a set of demo posts (location, item, timeframe, bookings, page) so that
 * new users can explore the plugin immediately after installation.
 *
 * The "Try with demo data" button on the dashboard is controlled by shouldShowButton():
 *  - hidden once any CB custom post type data exists
 *  - hidden after 7 days from plugin installation
 *  - hidden once the demo data has already been created
 */
class DemoData {

	const CREATED_OPTION      = COMMONSBOOKING_PLUGIN_SLUG . '_demo_data_created';
	const INSTALL_DATE_OPTION = COMMONSBOOKING_PLUGIN_SLUG . '_install_date';

	/**
	 * Returns true when the "Try with demo data" button should be visible.
	 */
	public static function shouldShowButton(): bool {
		// Already clicked once
		if ( self::hasBeenCreated() ) {
			return false;
		}

		// Any CB CPT data already exists
		foreach ( [ 'cb_location', 'cb_item', 'cb_timeframe', 'cb_booking' ] as $postType ) {
			$posts = get_posts( [
				'post_type'      => $postType,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );
			if ( ! empty( $posts ) ) {
				return false;
			}
		}

		// More than 7 days since installation
		$installDate = get_option( self::INSTALL_DATE_OPTION );
		if ( $installDate && ( time() - (int) $installDate ) > ( 7 * DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns true when demo data has already been created.
	 */
	public static function hasBeenCreated(): bool {
		return (bool) get_option( self::CREATED_OPTION );
	}

	/**
	 * Creates the full demo dataset and marks it as done.
	 *
	 * @return array{ page_url: string, page_id: int }
	 */
	public static function create(): array {
		$adminId = get_current_user_id() ?: 1;

		// --- Location ---
		$locationId = wp_insert_post( [
			'post_title'  => 'Demo Location',
			'post_type'   => 'cb_location',
			'post_status' => 'publish',
			'post_author' => $adminId,
		] );

		// --- Item ---
		$itemId = wp_insert_post( [
			'post_title'  => 'Demo Item',
			'post_type'   => 'cb_item',
			'post_status' => 'publish',
			'post_author' => $adminId,
		] );

		// --- Bookable Timeframe: -60 days to +60 days ---
		$timeframeId = wp_insert_post( [
			'post_title'  => 'Demo Timeframe',
			'post_type'   => 'cb_timeframe',
			'post_status' => 'publish',
			'post_author' => $adminId,
		] );
		update_post_meta( $timeframeId, 'type', Timeframe::BOOKABLE_ID );
		update_post_meta( $timeframeId, 'location-id', $locationId );
		update_post_meta( $timeframeId, 'item-id', $itemId );
		update_post_meta( $timeframeId, 'repetition-start', strtotime( '-60 days' ) );
		update_post_meta( $timeframeId, 'repetition-end', strtotime( '+60 days' ) );
		update_post_meta( $timeframeId, 'full-day', 'on' );
		update_post_meta( $timeframeId, 'timeframe-repetition', 'w' );
		update_post_meta( $timeframeId, 'weekdays', [ '1', '2', '3', '4', '5', '6', '7' ] );
		update_post_meta( $timeframeId, 'timeframe-max-days', 3 );
		update_post_meta( $timeframeId, 'timeframe-advance-booking-days', 30 );
		update_post_meta( $timeframeId, 'item-select', Timeframe::SELECTION_MANUAL_ID );
		update_post_meta( $timeframeId, 'location-select', Timeframe::SELECTION_MANUAL_ID );
		update_post_meta( $timeframeId, 'start-time', '8:00 AM' );
		update_post_meta( $timeframeId, 'end-time', '12:00 PM' );
		update_post_meta( $timeframeId, 'grid', 0 );

		// --- 3 Bookings (2 past, 1 future) ---
		$bookingDates = [
			[ strtotime( '-14 days midnight' ), strtotime( '-12 days midnight' ) - 1 ],
			[ strtotime( '-7 days midnight' ),  strtotime( '-5 days midnight' ) - 1  ],
			[ strtotime( '+3 days midnight' ),  strtotime( '+5 days midnight' ) - 1  ],
		];

		foreach ( $bookingDates as $dates ) {
			$bookingId = wp_insert_post( [
				'post_title'  => 'Demo Booking',
				'post_type'   => 'cb_booking',
				'post_status' => 'confirmed',
				'post_author' => $adminId,
			] );
			update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
			update_post_meta( $bookingId, 'location-id', $locationId );
			update_post_meta( $bookingId, 'item-id', $itemId );
			update_post_meta( $bookingId, 'repetition-start', $dates[0] );
			update_post_meta( $bookingId, 'repetition-end', $dates[1] );
			update_post_meta( $bookingId, 'start-time', '12:00 AM' );
			update_post_meta( $bookingId, 'end-time', '23:59' );
			update_post_meta( $bookingId, 'timeframe-repetition', 'w' );
			update_post_meta( $bookingId, 'timeframe-max-days', 3 );
			update_post_meta( $bookingId, 'grid', 0 );
			update_post_meta( $bookingId, 'weekdays', [ '1', '2', '3', '4', '5', '6', '7' ] );
		}

		// --- Private page with [cb_bookings] shortcode ---
		$pageId = wp_insert_post( [
			'post_title'   => 'My Bookings',
			'post_content' => '[cb_bookings]',
			'post_status'  => 'private',
			'post_type'    => 'page',
			'post_author'  => $adminId,
		] );

		update_option( self::CREATED_OPTION, true );

		return [
			'page_id'  => $pageId,
			'page_url' => get_permalink( $pageId ),
		];
	}

	/**
	 * AJAX handler for the "Try with demo data" button.
	 * Expects POST: action=cb_create_demo_data, nonce=<cb_create_demo_data nonce>
	 */
	public static function ajaxCreateDemoData(): void {
		check_ajax_referer( 'cb_create_demo_data', 'nonce' );

		if ( ! current_user_can( 'manage_' . COMMONSBOOKING_PLUGIN_SLUG ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'commonsbooking' ) ] );
		}

		try {
			$result = self::create();
			wp_send_json_success( [
				'redirect_url' => admin_url( 'admin.php?page=cb-dashboard' ),
				'page_url'     => $result['page_url'],
			] );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
