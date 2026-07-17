<?php

namespace CommonsBooking\Migration;

use CommonsBooking\Repository\Timeframe;

class Booking {

	/**
	 * Changes post type of booking timeframes to cb_booking.
	 */
	public static function migrate() {

		$bookings = Timeframe::getPostIdsByType(
			[
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_CANCELED_ID,
			]
		);

		foreach ( $bookings as $booking ) {
			set_post_type( $booking, \CommonsBooking\Wordpress\CustomPostType\Booking::$postType );
		}

		return $bookings;
	}

	/**
	 * Runs migration script and returns booking IDs as json array.
	 *
	 * @return void
	 */
	public static function ajaxMigrate() {
		wp_send_json( self::migrate() );
	}
}
