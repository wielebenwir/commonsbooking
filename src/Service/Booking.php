<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\BookingReminderMessage;
use CommonsBooking\Messages\LocationBookingReminderMessage;
use CommonsBooking\Messages\Message;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use WP_Query;

class Booking {

	/**
	 * Removes all unconfirmed bookings older than 10 minutes
	 * is triggered in  Service\Scheduler initHooks()
	 *
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
	 * @param class-string<Message> $messageClass
	 */
	private static function sendMessagesForDay( int $tsDate, bool $onStartDate, string $messageClass, string $messageAction ): void {
		if ( $onStartDate ) {
			$bookings = \CommonsBooking\Repository\Booking::getBeginningBookingsByDate( $tsDate );
		} else {
			$bookings = \CommonsBooking\Repository\Booking::getEndingBookingsByDate( $tsDate );
		}
		if ( count( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				if ( $booking->hasTotalBreakdown() ) {
					continue;
				}
				$message = new $messageClass( $booking->getPost()->ID, $messageAction );
				$message->triggerMail();
			}
		}
	}

	/**
	 * Send reminder mail, x days before start of booking.
	 * is triggered in  Service\Scheduler initHooks()
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function sendReminderMessage(): void {

		if ( Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-reminder-activate' ) != 'on' ) {
			return;
		}

		$daysBeforeStart = Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-days-before' );
		self::sendMessagesForDay( strtotime( '+' . $daysBeforeStart . ' days midnight' ), true, BookingReminderMessage::class, 'pre-booking-reminder' );
	}

	/**
	 * Send feedback mal on same day or the day after end of booking.
	 * is triggered in  Service\Scheduler initHooks()
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function sendFeedbackMessage(): void {

		if ( Settings::getOption( 'commonsbooking_options_reminder', 'post-booking-notice-activate' ) != 'on' ) {
			return;
		}

		// Yesterday at 23:59
		$endDate = strtotime( 'midnight', time() ) - 1;
		self::sendMessagesForDay( $endDate, false, BookingReminderMessage::class, 'post-booking-notice' );
	}

	public static function sendBookingStartLocationReminderMessage(): void {
		self::sendLocationBookingReminderMessage( 'start' );
	}

	public static function sendBookingEndLocationReminderMessage(): void {
		self::sendLocationBookingReminderMessage( 'end' );
	}

	protected static function sendLocationBookingReminderMessage( string $type ): void {

		$action = 'booking-' . $type . '-location-reminder';

		if ( Settings::getOption( 'commonsbooking_options_reminder', $action . '-activate' ) != 'on' ) {
			return;
		}

		// current day is saved in options as 1, this is because 0 is an unset value. Subtract 1 to get the correct day
		$daysBeforeStart = (int) Settings::getOption( 'commonsbooking_options_reminder', $action . '-day' ) - 1;
		$startDate       = strtotime( '+' . $daysBeforeStart . ' days midnight' );

		self::sendMessagesForDay( $startDate, $type === 'start', LocationBookingReminderMessage::class, $action );
	}
}
