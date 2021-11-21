<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\BookingReminderMessage;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use WP_Query;

class Booking {

	// Removes all unconfirmed bookings older than 10 minutes
	public static function cleanupBookings() {
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

	/**
	 * Send reminder mail, x days before start of booking.
	 * @throws \Exception
	 */
	public static function sendReminderMessage() {
		$daysBeforeStart = Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-days-before' );
		$startDate       = strtotime( '+' . $daysBeforeStart . ' days midnight', time() );

		// Get bookings starting on targeted startdate
		$bookings = \CommonsBooking\Repository\Booking::getByTimerange(
			$startDate,
			strtotime( '2222-01-01' ),
			null,
			null
		);

		if ( count( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				$reminderMessage = new BookingReminderMessage( $booking->getPost()->ID, 'pre-booking-reminder' );
				$reminderMessage->sendMessage();
			}
		}
	}

	/**
	 * Send feedback mal on same day or the day after end of booking.
	 * @throws \Exception
	 */
	public static function sendFeedbackMessage() {
		$daysAfter = Settings::getOption( 'commonsbooking_options_reminder', 'post-booking-notice-days-after' );

		$endDate = strtotime( 'tomorrow midnight', time() ) - 1;
		if ( $daysAfter != 'sameday' ) {
			$endDate = strtotime( 'midnight', time() ) - 1;
		}

		// Get bookings ending on the targeted enddate
		$bookings = \CommonsBooking\Repository\Booking::getByTimerange(
			0,
			$endDate,
			null,
			null
		);

		if ( count( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				$reminderMessage = new BookingReminderMessage( $booking->getPost()->ID, 'post-booking-notice' );
				$reminderMessage->sendMessage();
			}
		}
	}

}