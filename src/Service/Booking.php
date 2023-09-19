<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\BookingReminderMessage;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use WP_Query;

class Booking {

	/**
	 * Removes all unconfirmed bookings older than 10 minutes
	 * is triggered in  Service\Scheduler initHooks()
	 * @return void
	 */
	public static function cleanupBookings() {
		$args = array(
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
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
     * is triggered in  Service\Scheduler initHooks()
	 * @throws \Exception
	 */
	public static function sendReminderMessage() {

        if (Settings::getOption('commonsbooking_options_reminder', 'pre-booking-reminder-activate') != 'on') {
            return;
        }

		$daysBeforeStart = Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-days-before' );
		$startDate       = strtotime( '+' . $daysBeforeStart . ' days midnight' );

		// Startday of booking at 23:59
		$endDate = strtotime( '+23 Hours +59 Minutes +59 Seconds', $startDate );

		// Add filter to get only bookings ending on day of enddate
		$customArgs['meta_query'][] = array(
			'key'     => \CommonsBooking\Model\Booking::REPETITION_START,
			'value'   => array( $startDate, $endDate ),
			'compare' => 'BETWEEN',
			'type'    => 'numeric'
		);

		// Get bookings starting on targeted startdate
		$bookings = \CommonsBooking\Repository\Booking::getByTimerange(
			$startDate,
			strtotime( '2222-01-01' ),
			null,
			null,
			$customArgs,
			['confirmed']
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
     * is triggered in  Service\Scheduler initHooks()
	 * @throws \Exception
	 */
	public static function sendFeedbackMessage() {

        if (Settings::getOption('commonsbooking_options_reminder', 'post-booking-notice-activate') != 'on') {
            return;
        }

		// Yesterday at 23:59
		$endDate = strtotime( 'midnight', time() ) - 1;

		// Add filter to get only bookings ending on day of enddate
		$customArgs['meta_query'][] = array(
			'key'     => \CommonsBooking\Model\Booking::REPETITION_END,
			'value'   => array( strtotime( 'midnight', $endDate ), $endDate ),
			'compare' => 'BETWEEN',
			'type'    => 'numeric'
		);

		// Get bookings ending on the targeted enddate
		$bookings = \CommonsBooking\Repository\Booking::getByTimerange(
			0,
			$endDate,
			null,
			null,
			$customArgs
		);

		if ( count( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				$reminderMessage = new BookingReminderMessage( $booking->getPost()->ID, 'post-booking-notice' );
				$reminderMessage->sendMessage();
			}
		}
	}

}