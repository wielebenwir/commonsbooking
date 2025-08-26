<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Messages\BookingReminderMessage;
use CommonsBooking\Messages\LocationBookingReminderMessage;
use CommonsBooking\Messages\Message;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
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

	private static function sendMessagesForDay( int $tsDate, bool $onStartDate, Message $message ) {
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
				$message = new $message( $booking->getPost()->ID, $message->getAction() );
				$message->triggerMail();
			}
		}
	}

	/**
	 * Send reminder mail, x days before start of booking.
	 * is triggered in  Service\Scheduler initHooks()
	 *
	 * @throws \Exception
	 */
	public static function sendReminderMessage() {

		if ( Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-reminder-activate' ) != 'on' ) {
			return;
		}

		$message         = new BookingReminderMessage( 0, 'pre-booking-reminder' );
		$daysBeforeStart = Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-days-before' );
		self::sendMessagesForDay( strtotime( '+' . $daysBeforeStart . ' days midnight' ), true, $message );
	}

	/**
	 * Send feedback mal on same day or the day after end of booking.
	 * is triggered in  Service\Scheduler initHooks()
	 *
	 * @throws \Exception
	 */
	public static function sendFeedbackMessage() {

		if ( Settings::getOption( 'commonsbooking_options_reminder', 'post-booking-notice-activate' ) != 'on' ) {
			return;
		}

		// Yesterday at 23:59
		$endDate = strtotime( 'midnight', time() ) - 1;
		$message = new BookingReminderMessage( 0, 'post-booking-notice' );
		self::sendMessagesForDay( $endDate, false, $message );
	}

	public static function sendBookingStartLocationReminderMessage() {
		self::sendLocationBookingReminderMessage( 'start' );
	}

	public static function sendBookingEndLocationReminderMessage() {
		self::sendLocationBookingReminderMessage( 'end' );
	}

	protected static function sendLocationBookingReminderMessage( string $type ) {

		if ( Settings::getOption( 'commonsbooking_options_reminder', 'booking-' . $type . '-location-reminder-activate' ) != 'on' ) {
			return;
		}

		// current day is saved in options as 1, this is because 0 is an unset value. Subtract 1 to get the correct day
		$daysBeforeStart = (int) Settings::getOption( 'commonsbooking_options_reminder', 'booking-' . $type . '-location-reminder-day' ) - 1;
		$startDate       = strtotime( '+' . $daysBeforeStart . ' days midnight' );

		$message = new LocationBookingReminderMessage( 0, 'booking-' . $type . '-location-reminder' );
		self::sendMessagesForDay( $startDate, $type === 'start', $message );
	}

	/**
	 *
	 * Will handle the frontend booking request. We moved this to a separate function
	 * so that we can test it.
	 *
	 * @param string|null $itemId
	 * @param string|null $locationId
	 * @param string|null $post_status
	 * @param int|null    $post_ID
	 * @param string|null $comment
	 * @param string|null $repetitionStart
	 * @param string|null $repetitionEnd
	 * @param string|null $requestedPostName
	 * @param string|null $postType
	 *
	 * @return int - the post id of the created booking
	 * @throws BookingDeniedException - if the booking is not possible, message contains translated text for the user
	 */
	public static function handleBookingRequest(
		?string $itemId,
		?string $locationId,
		?string $post_status,
		?int $post_ID,
		?string $comment,
		?string $repetitionStart,
		?string $repetitionEnd,
		?string $requestedPostName,
		?string $postType,
		int $overbookedDays = 0
	): int {

		if ( isset( $_POST['calendar-download'] ) ) {
			try {
				iCalendar::downloadICS( $post_ID );
			} catch ( Exception $e ) {
				// redirect to booking page and do nothing
				return $post_ID;
			}
			exit;
		}

		if ( $itemId === null || ! filter_var( $itemId, FILTER_VALIDATE_INT ) || ! get_post( (int) $itemId ) ) {
			// translators: $s = id of the item
			throw new BookingDeniedException( sprintf( __( 'Item does not exist. (%s)', 'commonsbooking' ), $itemId ) );
		}

		if ( $locationId === null || ! filter_var( $locationId, FILTER_VALIDATE_INT ) || ! get_post( (int) $locationId ) ) {
			// translators: $s = id of the location
			throw new BookingDeniedException( sprintf( __( 'Location does not exist. (%s)', 'commonsbooking' ), $locationId ) );
		}

		if ( $repetitionStart === null || $repetitionEnd === null ) {
			throw new BookingDeniedException( __( 'Start- and/or end-date is missing.', 'commonsbooking' ) );
		}

		// Validation end, set correctly typed params
		$itemId          = (int) $itemId;
		$locationId      = (int) $locationId;
		$repetitionStart = (int) $repetitionStart;
		$repetitionEnd   = (int) $repetitionEnd;

		if ( $post_ID != null && ! get_post( $post_ID ) ) {
			throw new BookingDeniedException(
				__( 'Your reservation has expired, please try to book again', 'commonsbooking' ),
				add_query_arg( 'cb-location', $locationId, get_permalink( get_post( $itemId ) ) )
			);
		}

		$booking = \CommonsBooking\Repository\Booking::getByDate(
			$repetitionStart,
			$repetitionEnd,
			$locationId,
			$itemId
		);

		$existingBookings =
			\CommonsBooking\Repository\Booking::getExistingBookings(
				$itemId,
				$locationId,
				$repetitionStart,
				$repetitionEnd,
				$booking->ID ?? null,
			);

		// delete unconfirmed booking if booking process is canceled by user
		if ( $post_status === 'delete_unconfirmed' && $booking->ID === $post_ID ) {
			wp_delete_post( $post_ID );
			throw new BookingDeniedException(
				__( 'Booking canceled.', 'commonsbooking' ),
				add_query_arg( 'cb-location', $locationId, get_permalink( get_post( $itemId ) ) )
			);
		}

		// Validate booking -> check if there are no existing bookings in timerange.
		if ( count( $existingBookings ) > 0 ) {
			// checks if it's an edit, but ignores exact start/end time
			$isEdit = count( $existingBookings ) === 1 &&
						array_values( $existingBookings )[0]->getPost()->post_name === $requestedPostName &&
						intval( array_values( $existingBookings )[0]->getPost()->post_author ) === get_current_user_id();

			if ( ( ! $isEdit || count( $existingBookings ) > 1 ) && $post_status !== 'canceled' ) {
				if ( $booking ) {
					$post_status = 'unconfirmed';
				} else {
					throw new BookingDeniedException( __( 'There is already a booking in this time-range. This notice may also appear if there is an unconfirmed booking in the requested period. Unconfirmed bookings are deleted after about 10 minutes. Please try again in a few minutes.', 'commonsbooking' ) );
				}
			}
		}

		// add internal comment if admin edited booking via frontend TODO: This does not happen anymore, no admin bookings are made through the frontend
		if ( $booking && $booking->post_author !== '' && intval( $booking->post_author ) !== intval( get_current_user_id() ) ) {
			$postarr['meta_input']['admin_booking_id'] = get_current_user_id();
			$internal_comment                          = esc_html__( 'status changed by admin user via frontend. New status: ', 'commonsbooking' ) . $post_status;
			$booking->appendToInternalComment( $internal_comment, get_current_user_id() );
		}

		$postarr['type']                  = $postType;
		$postarr['post_status']           = $post_status;
		$postarr['post_type']             = CustomPostType::getPostType();
		$postarr['post_title']            = esc_html__( 'Booking', 'commonsbooking' );
		$postarr['meta_input']['comment'] = $comment;

		// New booking
		if ( empty( $booking ) ) {
			$postarr['post_name']  = Helper::generateRandomString();
			$postarr['meta_input'] = array(
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID => $locationId,
				\CommonsBooking\Model\Timeframe::META_ITEM_ID     => $itemId,
				\CommonsBooking\Model\Timeframe::REPETITION_START => $repetitionStart,
				\CommonsBooking\Model\Timeframe::REPETITION_END   => $repetitionEnd,
				'type'                                            => Timeframe::BOOKING_ID,
			);

			$postId          = wp_insert_post( $postarr, true );
			$needsValidation = true;

			// Existing booking
		} else {
			$postarr['ID'] = $booking->ID;
			if ( $postarr['post_status'] === 'canceled' ) {
				$postarr['meta_input']['cancellation_time'] = current_time( 'timestamp' );
			}
			$postId = wp_update_post( $postarr );

			// we check if this is an already denied booking and demand validation again
			if ( $postarr['post_status'] == 'unconfirmed' ) {
				$needsValidation = true;
			} else {
				$needsValidation = false;
			}
		}

		self::saveGridSizes( $postId, $locationId, $itemId, $repetitionStart, $repetitionEnd );

		$bookingModel = \CommonsBooking\Repository\Booking::getPostById( $postId );
		// we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
		try {
			$bookingModel->assignBookableTimeframeFields();
			if ( $overbookedDays > 0 ) { // avoid setting the value when not present (for example when updating the booking)
				$bookingModel->setOverbookedDays( $overbookedDays );
			}
		} catch ( \Exception $e ) {
			throw new BookingDeniedException(
				__( 'There was an error while saving the booking. Please try again. Thrown error:', 'commonsbooking' ) .
				PHP_EOL . $e->getMessage()
			);
		}

		// check if the Booking we want to create conforms to the set booking rules
		if ( $needsValidation ) {
			try {
				BookingRuleApplied::bookingConformsToRules( $bookingModel );
			} catch ( BookingDeniedException $e ) {
				wp_delete_post( $bookingModel->ID );
				throw new BookingDeniedException( $e->getMessage() );
			}
		}

		if ( $postId instanceof \WP_Error ) {
			throw new BookingDeniedException(
				__( 'There was an error while saving the booking. Please try again. Resulting WP_ERROR: ', 'commonsbooking' ) .
				PHP_EOL . implode( ', ', $postId->get_error_messages() )
			);
		}

		return $postId;
	}

	/**
	 * Multi grid size
	 * We need to save the grid size for timeframes with full slot grid.
	 *
	 * @param $postId
	 * @param $locationId
	 * @param $itemId
	 * @param $startDate
	 * @param $endDate
	 */
	private static function saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate ): void {
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && ! $startTimeFrame->isFullDay() && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && ! $endTimeFrame->isFullDay() && $endTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE,
				$endTimeFrame->getGridSize()
			);
		}
	}
}
