<?php


namespace CommonsBooking\Model;

use CommonsBooking\Exception\BookingCodeException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Helper\Wordpress;
use Exception;

use CommonsBooking\CB\CB;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Messages\BookingMessage;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Service\iCalendar;
use DateTime;
use WP_User;

/**
 * Logical wrapper for `booking` posts
 * Bookings used to be just a type of `timeframe` post, but now they are a separate post type.
 * This leads to a lot of post meta for bookings that only make sense in a timeframe context.
 *
 * Additionally, all the public functions in this class can be called through Template Tags.
 *
 * You can get the bookings from the database using the @see \CommonsBooking\Repository\Booking class.
 */
class Booking extends \CommonsBooking\Model\Timeframe {

	const START_TIMEFRAME_GRIDSIZE = 'start-timeframe-gridsize';

	const END_TIMEFRAME_GRIDSIZE = 'end-timeframe-gridsize';

	/**
	 * Meta value for the amount of days that have been overbooked for this booking.
	 */
	const META_OVERBOOKED_DAYS = 'days-overbooked';

	/**
	 * Usually not set, only set when changing a timeframe orphans the current booking.
	 */
	const META_LAST_TIMEFRAME = 'last-connected-timeframe';

	public const ERROR_TYPE = 'BookingValidationFailed';

	/**
	 * Booking states.
	 *
	 * @var string[]
	 */
	public static $bookingStates = [
		'canceled',
		'confirmed',
		'unconfirmed',
	];

	/**
	 * Returns the booking code as a string.
	 *
	 * @return mixed
	 */
	public function getBookingCode() {
		return $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'bookingcode' );
	}


	/**
	 * Determines if a current booking can be cancelled or not by the current user.
	 * Bookings which do not belong to the current user or the user is not admin of cannot be edited.
	 *
	 * Returns true if booking can be cancelled.
	 * False if booking may not be cancelled.
	 *
	 * @return bool
	 */
	public function canCancel(): bool {
		if ( $this->isPast() ) {
			return false;
		}

		if ( intval( $this->post_author ) === get_current_user_id() ) {
			return true;
		} elseif ( commonsbooking_isCurrentUserAllowedToEdit( $this->post ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Cancel the current booking and send a cancellation mail to the user.
	 * Because we are directly updating the database, we need another function to flush the database cache (wp_cache_flush()) to test this function.
	 */
	public function cancel(): void {

		// check if booking has ended
		if ( $this->isPast() ) {
			return;
		}

		// workaround, because wp_update_post deletes all meta data

		global $wpdb;
		$sql = $wpdb->prepare(
			'UPDATE ' . $wpdb->prefix . "posts SET post_status='canceled' WHERE ID = %d",
			$this->post->ID
		);
		$wpdb->query( $sql );

		update_post_meta( $this->post->ID, 'cancellation_time', current_time( 'timestamp' ) );

		$this->sendCancellationMail();
	}

	/**
	 * Send mail to booking user, that it was canceled.
	 *
	 * @return void
	 */
	protected function sendCancellationMail(): void {
		$booking_msg = new BookingMessage( $this->getPost()->ID, 'canceled' );
		$booking_msg->triggerMail();
	}

	/**
	 * Returns rendered booking code for using in email-template (booking confirmation mail).
	 * If booking code is not set, it returns an empty string.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function formattedBookingCode(): string {
		$htmloutput = '';
		if (
			$this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'bookingcode' ) &&
			$this->isConfirmed() && (
				$this->showBookingCodes() ||
				( $this->getBookableTimeFrame() && $this->getBookableTimeFrame()->showBookingCodes() )
			)
		) {
			// translators: %s = Booking code
			$htmloutput = '<br>' . sprintf( commonsbooking_sanitizeHTML( __( 'Your booking code is: %s', 'commonsbooking' ) ), $this->getBookingCode() ) . '<br>';
		}

		return $htmloutput;
	}

	/**
	 * Returns true if booking codes shall be shown in frontend.
	 *
	 * @return bool
	 */
	public function showBookingCodes(): bool {
		return $this->getMeta( self::META_SHOW_BOOKING_CODES ) === 'on';
	}

	/**
	 * Returns the corresponding Timeframe object for booking.
	 * If no timeframe is found, it returns null.
	 *
	 * @return null|\CommonsBooking\Model\Timeframe
	 * @throws Exception
	 */
	public function getBookableTimeFrame(): ?\CommonsBooking\Model\Timeframe {
		$locationId = $this->getMeta( \CommonsBooking\Model\Timeframe::META_LOCATION_ID );
		$itemId     = $this->getMeta( \CommonsBooking\Model\Timeframe::META_ITEM_ID );

		$response = Timeframe::getBookable(
			[ $locationId ],
			[ $itemId ],
			date( CB::getInternalDateFormat(), intval( $this->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ) ) ),
			true
		);

		if ( count( $response ) ) {
			return array_shift( $response );
		}

		return null;
	}

	/**
	 * Assigns relevant meta fields from related bookable timeframe to booking.
	 * We have to do this, because bookings used to be just a type of timeframe post.
	 * This leads to a lot of post meta for bookings that only make sense in a timeframe context.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function assignBookableTimeframeFields(): void {
		$timeframe = $this->getBookableTimeFrame();
		if ( $timeframe ) {
			$neededMetaFields = [
				'full-day',
				'grid',
				'start-time',
				'end-time',
				\CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES,
				\CommonsBooking\Model\Timeframe::META_MAX_DAYS,
			];
			foreach ( $neededMetaFields as $fieldName ) {
				$fieldValue = get_post_meta(
					$timeframe->ID,
					$fieldName,
					true
				);
				if ( in_array( $fieldName, [ 'start-time', 'end-time' ], true ) ) {
					$fieldValue = $this->sanitizeTimeField( $fieldName );
				}
				update_post_meta(
					$this->post->ID,
					$fieldName,
					$fieldValue
				);
			}

			// If there exists a booking code, add it.
			try {
				$bookingCode = BookingCodes::getCode(
					$timeframe,
					$this->getItemID(),
					$this->getLocationID(),
					date( 'Y-m-d', $this->getStartDate() )
				);
			} catch ( BookingCodeException $e ) {
				// do nothing, just set the booking code to null
				$bookingCode = null;
			}

			// only add booking code if the booking is based on a full day timeframe
			if ( $bookingCode && $this->isFullDay() ) {
				update_post_meta(
					$this->post->ID,
					COMMONSBOOKING_METABOX_PREFIX . 'bookingcode',
					$bookingCode->getCode()
				);
			}
		}
	}

	/**
	 * Gets the booking that is directly adjacent to this booking at the same place / location.
	 *
	 * @param bool $previous True: Get booking previous to this booking. False: Get booking following this booking.
	 * @return Booking|null
	 */
	private function adjacent( bool $previous = true ): ?Booking {
		if ( $previous ) {
			$startDate = $this->getStartDateDateTime()->modify( '-2 minutes' )->getTimestamp();
			$endDate   = $this->getStartDateDateTime()->modify( '-1 minute' )->getTimestamp();
		} else {
			$startDate = $this->getEndDateDateTime()->modify( '+1 minute' )->getTimestamp();
			$endDate   = $this->getEndDateDateTime()->modify( '+2 minutes' )->getTimestamp();
		}
		$adjacentBookings = \CommonsBooking\Repository\Booking::getByTimerange(
			$startDate,
			$endDate,
			$this->getLocationID(),
			$this->getItemID(),
			[],
			[ 'confirmed' ]
		);
		if ( count( $adjacentBookings ) == 1 ) {
			return reset( $adjacentBookings );
		} elseif ( count( $adjacentBookings ) > 1 ) {
			throw new Exception( 'Overlapping booking detected.' );
		} else {
			return null;
		}
	}

	/**
	 * Gets the bookings directly adjacent to the current booking (with same item / location of course)
	 *
	 * @since 2.9.0
	 *
	 * @return Booking[]
	 * @throws Exception
	 */
	public function getAdjacentBookings(): ?array {
		$previousAdjacent  = $this->adjacent();
		$followingAdjacent = $this->adjacent( false );
		return array_filter( [ $previousAdjacent, $followingAdjacent ] );
	}

	/**
	 * Get the bookings directly adjacent to the current users booking. Limited to a specific user to reduce load.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_User $user
	 * @return Booking[]
	 */
	public function getBookingChain( WP_User $user ): array {
		$bookingChain    = [];
		$previousBooking = $this->adjacent();
		if ( $previousBooking && $previousBooking->getUserData()->ID != $user->ID ) {
			$previousBooking = null;
		}
		$followingBooking = $this->adjacent( false );
		if ( $followingBooking && $followingBooking->getUserData()->ID != $user->ID ) {
			$followingBooking = null;
		}
		while ( $previousBooking != null ) {
			$bookingChain[]  = $previousBooking;
			$previousBooking = $previousBooking->adjacent();
			if ( $previousBooking && $previousBooking->getUserData()->ID != $user->ID ) {
				$previousBooking = null;
			}
		}
		while ( $followingBooking != null ) {
			$bookingChain[]   = $followingBooking;
			$followingBooking = $followingBooking->adjacent( false );
			if ( $followingBooking && $followingBooking->getUserData()->ID != $user->ID ) {
				$followingBooking = null;
			}
		}
		return $bookingChain;
	}

	/**
	 * Returns time from repetition-[start/end] field in format H:i.
	 * We need this meta-field in order to display the pick-up and return time to the user.
	 *
	 * @param string $fieldName
	 *
	 * @return string
	 */
	private function sanitizeTimeField( $fieldName ): string {
		$time       = Wordpress::getUTCDateTime();
		$fieldValue = $this->getStartDate();
		if ( $fieldName === 'end-time' ) {
			$fieldValue = $this->getRawEndDate();
		}
		$time->setTimestamp( $fieldValue );

		return $time->format( 'H:i' );
	}

	/**
	 * Gets the corresponding item object for this booking.
	 * If no item is found, it returns null.
	 * This should not happen, because a booking is always based on an item. But this might happen if the item was deleted.
	 *
	 * @return ?Item
	 * @throws Exception
	 */
	public function getItem(): ?Item {
		if ( $post = get_post( $this->getItemID() ) ) {
			return new Item( $post );
		}

		return null;
	}

	/**
	 * Gets the corresponding location object for this booking.
	 * If no location is found, it returns null.
	 * This should not happen, because a booking is always based on a location. But this might happen if the location was deleted.
	 *
	 * @return ?Location
	 * @throws Exception
	 */
	public function getLocation(): ?Location {
		if ( $post = get_post( $this->getLocationID() ) ) {
			return new Location( $post );
		}

		return null;
	}

	/**
	 * Will set the postmeta field for the amount of days that have been overbooked and were not counted.
	 * This value is written through the Booking request form and provides the "raw" days were overbooked.
	 * This method cleans up that value to only count the days that were not counted towards the maximum booking length.
	 *
	 * @since 2.9.0
	 *
	 * @param int $rawDaysOverbooked The raw days a booking spans over a locked / holiday.
	 * @return int The amount of those days that were not counted towards the maximum booking length.
	 */
	public function setOverbookedDays( int $rawDaysOverbooked ): int {
		$location             = $this->getLocation();
		$countLockdaysInRange = $location->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range' ) === 'on';
		// Evaluate to 0 even if getMeta returns '' (valid but non-existent meta-key for the post) or false (post-id invalid)
		$countLockdaysMaximum = intval( $location->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum' ) );

		if ( ! $countLockdaysInRange ) {
			$days = $rawDaysOverbooked;
		} elseif ( $countLockdaysMaximum === 0 ) {
			$days = 0;
		} else {
			$days = max( 0, $rawDaysOverbooked - $countLockdaysMaximum );
		}

		update_post_meta( $this->post->ID, self::META_OVERBOOKED_DAYS, $days );
		return $days;
	}

	/**
	 * Will get the amount of days that were not counted towards the maximum booking length because they were overbooked.
	 *
	 * @since 2.9.0
	 *
	 * @return int
	 */
	public function getOverbookedDays(): int {
		$metaField = $this->getMeta( self::META_OVERBOOKED_DAYS );
		if ( ! $metaField ) {
			return 0;
		}
		return intval( $metaField );
	}

	public function getFormattedStartDate(): string {
		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		return date_i18n( $date_format, $this->getStartDate() );
	}

	public function getFormattedEndDate(): string {
		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		return date_i18n( $date_format, $this->getRawEndDate() );
	}

	/**
	 * Get the booking date in a human-readable format.
	 * This is used in the booking confirmation email as a template tag.
	 *
	 * @return string
	 */
	public function formattedBookingDate(): string {

		$startdate = $this->getFormattedStartDate();
		$enddate   = $this->getFormattedEndDate();

		if ( $startdate === $enddate ) {
			/* translators: %s = date in WordPress defined format */
			return sprintf( sanitize_text_field( __( ' on %s ', 'commonsbooking' ) ), $startdate );
		} else {
			/* translators: %1 = startdate, %2 = enddate in WordPress defined format */
			return sprintf( sanitize_text_field( __( ' from %1$s until %2$s ', 'commonsbooking' ) ), $startdate, $enddate );
		}
	}


	/**
	 *
	 * renders the pickup date and time information and returns a formatted string
	 * this is used in templates/booking-single.php and in email-templates (configuration via admin options)
	 *
	 * @return string
	 */
	public function pickupDatetime(): string {

		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		$time_format = commonsbooking_sanitizeHTML( get_option( 'time_format' ) );

		$repetitionStart = $this->getStartDate();
		$repetitionEnd   = $this->getEndDate();

		$date_start = date_i18n( $date_format, $repetitionStart );
		$time_start = date_i18n( $time_format, $repetitionStart );
		$time_end   = date_i18n( $time_format, $repetitionEnd );

		if ( $this->isFullDay() ) {
			return $date_start;
		}

		$grid = $this->getGrid();

		// the pickup and return time is not obvious from the booking. For example, if the
		// booking spans over two different slot based timeframes we would need to save one start-time and end-time for the pickup and one for the return
		// this is why we consult the underlying timeframe to get the slot duration. (See #1865)
		if ( $grid === 0 ) { // if grid is set to slot duration
			// If we have the grid size, we use it to calculate right time end
			$timeframeGridSize = $this->getMeta( self::START_TIMEFRAME_GRIDSIZE );
			if ( is_numeric( $timeframeGridSize ) ) {
				$grid = intval( $timeframeGridSize );
			}
		}

		if ( $grid > 0 ) { // if grid is set to hourly (grid = 1) or a multiple of an hour
			$time_end = date_i18n( $time_format, $repetitionStart + ( 60 * 60 * $grid ) );
		}

		return $date_start . ' ' . $time_start . ' - ' . $time_end;
	}

	/**
	 *
	 * renders the return date and time information and returns a formatted string
	 * this is used in templates/booking-single.php and in email-templates (configuration via admin options)
	 *
	 * @return string
	 */
	public function returnDatetime(): string {
		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		$time_format = commonsbooking_sanitizeHTML( get_option( 'time_format' ) );

		$date_end   = date_i18n( $date_format, $this->getRawEndDate() );
		$time_end   = date_i18n( $time_format, $this->getRawEndDate() + 60 ); // we add 60 seconds because internal timestamp is set to hh:59
		$time_start = date_i18n( $time_format, $this->getStartDate() );

		if ( $this->isFullDay() ) {
			return $date_end;
		}

		$grid = $this->getGrid();

		if ( $grid === 0 ) { // if grid is set to slot duration
			// If we have the grid size, we use it to calculate right time start
			$timeframeGridSize = $this->getMeta( self::END_TIMEFRAME_GRIDSIZE );
			if ( is_numeric( $timeframeGridSize ) ) {
				$grid = intval( $timeframeGridSize );
			}
		}

		if ( $grid > 0 ) { // if grid is set to hourly (grid = 1) or a multiple of an hour
			$time_start = date_i18n( $time_format, $this->getRawEndDate() + 1 - ( 60 * 60 * $grid ) );
		}

		return $date_end . ' ' . $time_start . ' - ' . $time_end;
	}

	/**
	 *
	 * Get the content of the repetition start meta field.
	 * This is a timestamp in local time. (not in UTC).
	 * That means we do not have to do timezone conversion in order to get the corresponding local time.
	 *
	 * TODO: Clarify why this implementation is different from the one in the parent class.
	 *
	 * @return int
	 */
	public function getStartDate(): int {
		return intval( $this->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ) );
	}

	/**
	 * Get the content of the repetition end meta field.
	 * This is a timestamp in local time. (does not start at UTC).
	 * That means we do not have to do timezone conversion in order to get the corresponding local time.
	 *
	 * TODO: Clarify why this implementation is different from the one in the parent class.
	 *
	 * @return int
	 */
	public function getEndDate(): int {
		return intval( $this->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_END ) );
	}

	/**
	 * Returns comment field text.
	 * The booking comment is a field that can be filled in by the user when booking (when enabled).
	 * The content of the field is not publicly visible and is only visible to the admin(s) and the user who made the booking.
	 *
	 * @return string
	 */
	public function returnComment(): string {
		return commonsbooking_sanitizeHTML( $this->getMeta( 'comment' ) );
	}

	/**
	 * show booking notice.
	 * The booking notice shows the current status of the booking to the user.
	 * This can be a confirmation, a cancellation or a notice that the booking could not be confirmed.
	 *
	 * @return string
	 */
	public function bookingNotice(): ?string {

		$cancellationTime = $this->getMeta( 'cancellation_time' );

		if ( $this->isUnconfirmed() ) {
			$noticeText = commonsbooking_sanitizeHTML( __( 'Please check your booking and click confirm booking', 'commonsbooking' ) );
		} elseif ( $this->isConfirmed() ) {
			$noticeText = commonsbooking_sanitizeHTML( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'booking-confirmed-notice' ) );
		} elseif ( $this->isCancelled() ) {
			if ( $cancellationTime ) {
				$cancellationTimeFormatted = Helper::FormattedDateTime( $cancellationTime );
				$noticeText                = sprintf( commonsbooking_sanitizeHTML( __( 'Your booking has been canceled at %s.', 'commonsbooking' ) ), $cancellationTimeFormatted );
			} else {
				$noticeText = commonsbooking_sanitizeHTML( __( 'Your booking has been canceled', 'commonsbooking' ) );
			}
		}

		if ( isset( $noticeText ) ) {
			return sprintf( '<div class="cb-notice cb-booking-notice cb-status-%s">%s</div>', $this->post_status, $noticeText );
		}

		return null;
	}

	/**
	 * Will check if a backend booking is valid.
	 * Throws a TimeframeInvalidException containing the error message if the booking is not valid.
	 *
	 * @return true if booking is valid
	 * @throws TimeframeInvalidException
	 */
	public function isValid(): bool {
		if ( $this->getStartDate() > $this->getEndDate() ) {
			throw new TimeframeInvalidException( 'Start date is after end date' );
		}

		try {
			$item = $this->getItem();
			if ( ! $item ) {
				throw new Exception();
			}
		} catch ( Exception $e ) {
			throw new TimeframeInvalidException( __( 'Item not found', 'commonsbooking' ) );
		}

		try {
			$location = $this->getLocation();
			if ( ! $location ) {
				throw new Exception();
			}
		} catch ( Exception $e ) {
			throw new TimeframeInvalidException( __( 'Location not found', 'commonsbooking' ) );
		}

		$timeframe = $this->getBookableTimeFrame();
		if ( $timeframe === null ) {
			throw new TimeframeInvalidException( __( 'There is no timeframe for this booking. Please create a timeframe first.', 'commonsbooking' ) );
		}

		// validate if overlapping bookings exist
		$overlappingBookings = \CommonsBooking\Repository\Booking::getExistingBookings(
			$item->ID,
			$location->ID,
			$this->getStartDate(),
			$this->getEndDate(),
			$this->ID
		);

		if ( count( $overlappingBookings ) >= 1 ) {
			foreach ( $overlappingBookings as $overlappingBooking ) {
				$overlappingBookingLinks[] = $overlappingBooking->getFormattedEditLink();
			}

			$formattedOverlappingLinks = implode( '<br>', $overlappingBookingLinks );

			throw new TimeframeInvalidException(
				__( 'There are one ore more overlapping bookings within the chosen timerange', 'commonsbooking' ) . PHP_EOL .
				__( 'Please adjust the start- or end-date.', 'commonsbooking' ) . PHP_EOL .
				sprintf( __( 'Affected Bookings: %s', 'commonsbooking' ), commonsbooking_sanitizeHTML( $formattedOverlappingLinks ) ),
			);
		}
		return true;
	}

	/**
	 * Render HTML Link to booking.
	 * This is not just the URL but a complete HTML link with corresponding text.
	 * This function is used in the booking confirmation email via template tags.
	 *
	 * @TODO: optimize booking link to support different permalink settings or set individual slug (e.g. booking instead of cb_timeframe)
	 *
	 * @param string|null $linktext
	 *
	 * @return string
	 */
	public function bookingLink( $linktext = null ): string {

		// if no linktext is set we use standard text
		if ( $linktext === null ) {
			$linktext = esc_html__( 'Link to your booking', 'commonsbooking' );
		}

		return sprintf( '<a href="%1$s">%2$s</a>', $this->bookingLinkUrl(), $linktext );
	}

	/**
	 * return plain booking URL
	 *
	 * @return string
	 */
	public function bookingLinkUrl(): string {
		return add_query_arg( $this->post->post_type, $this->post->post_name, home_url( '/' ) );
	}

	/**
	 * Returns true when booking is cancelled. This might not correctly reflect the status of the booking when $this->cancel() has been called.
	 * In order to correctly reflect this, you need to call wp_cache_flush() before calling this function.
	 *
	 * @return bool
	 */
	public function isCancelled(): bool {
		return $this->post_status === 'canceled';
	}

	/**
	 * Checks if the given user / current user is administrator of item / location of the booking or of the whole website and therefore enjoys special booking rights
	 *
	 * @since 2.9.0
	 *
	 * @param WP_User|null $user
	 * @return bool
	 */
	public function isUserPrivileged( WP_User $user = null ): bool {
		$user ??= $this->getUserData();
		return parent::isUserPrivileged( $user );
	}

	/**
	 * Will indicate if booking is orphaned meaning that the booking is not connectable to a bookable timeframe.
	 * This can happen when a bookable timeframe is deleted but the booking is still in the database,
	 * when a bookable timeframe has a location changed and the booking is still in the database,
	 * or when a bookable timeframe is changed to a non-bookable timeframe and the booking is still in the database.
	 *
	 * This can also happen when a booking is created without a bookable timeframe, e.g. when a booking is created through the backend.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isOrphaned(): bool {
		if ( $this->getBookableTimeFrame() === null ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns true when booking has ended.
	 * Will determine this by comparing the end date of the booking with the current time.
	 * A booking that is currently running is not considered to be past.
	 *
	 * @return bool
	 */
	public function isPast(): bool {
		if ( $this->getEndDate() < current_time( 'timestamp' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Will check if a single term / multiple terms are applicable for the current bookings location or item
	 *
	 * @since 2.9.0
	 *
	 * @param int|string|array<int|string> $term
	 * @return bool
	 */
	public function termsApply( $term ): bool {
		try {
			$item     = $this->getItem();
			$location = $this->getLocation();
		} catch ( Exception $e ) {
			// terms are not applicable if either location or item is not found
			return false;
		}
		$isInItemCat     = has_term( $term, \CommonsBooking\Wordpress\CustomPostType\Item::getTaxonomyName(), $item->getPost() );
		$isInLocationCat = has_term( $term, \CommonsBooking\Wordpress\CustomPostType\Location::getTaxonomyName(), $location->getPost() );
		return ( $isInItemCat || $isInLocationCat );
	}

	/**
	 * Gets the length of a booking in days.
	 * The behaviour of this function depends on the type of booking.
	 * When a booking is confirmed or unconfirmed, it will return the whole amount of days in the booking interval.
	 * When a booking is cancelled, it will only return the amount of days the item has been "used" (from start to cancellation).
	 *
	 * A day is counted after the first hour of the day has passed.
	 *
	 * @since 2.9.0
	 *
	 * @return int
	 */
	public function getDuration(): int {
		$interval = null;
		if ( $this->isUnconfirmed() || $this->isConfirmed() ) {
			$interval = $this->getStartDateDateTime()->diff( $this->getEndDateDateTime()->modify( '+5 min' ) );
		} elseif ( $this->isCancelled() ) {
			$startDate        = $this->getStartDateDateTime();
			$cancellationDate = $this->getCancellationDateDateTime();
			// count as 0 days when booking is cancelled before it has started
			if ( $cancellationDate < $startDate ) {
				return 0;
			}
			$interval = $startDate->diff( $cancellationDate );
		} else {
			// Booking has no valid status
			return 0;
		}

		$days = $interval->d;
		// when we have already moved into the next day for more one hour,it is counted as another day even if it is not completed
		if ( $interval->h > 0 ) {
			++$days;
		}
		return $days - $this->getOverbookedDays();
	}

	/**
	 * Will get the DateTime object of the cancellation date.
	 * The cancellation date will be saved as postmeta when a booking is cancelled.
	 * Will return null when the booking is not cancelled.
	 *
	 * @since 2.9.0
	 *
	 * @return DateTime|null
	 * @throws Exception
	 */
	public function getCancellationDateDateTime(): ?DateTime {
		if ( ! $this->isCancelled() ) {
			return null;
		}
		$cancellationTimestamp = $this->getMeta( 'cancellation_time' );
		return Wordpress::getUTCDateTimeByTimestamp( $cancellationTimestamp );
	}

	/**
	 * Will get an iCalendar with just this booking as an event.
	 * This is used to attach the iCalendar to the booking confirmation email.
	 *
	 * @param string $eventTitle
	 * @param string $eventDescription
	 *
	 * @return string
	 */
	public function getiCal(
		string $eventTitle,
		string $eventDescription
	): string {
		$calendar = new iCalendar();
		$calendar->addBookingEvent( $this, $eventTitle, $eventDescription );
		return $calendar->getCalendarData();
	}

	/**
	 * Helper to return the email signature configured in the options array
	 *
	 * @return string
	 */
	public function getEmailSignature(): string {
		return commonsbooking_sanitizeHTML(
			Settings::getOption( 'commonsbooking_options_templates', 'emailbody_signature' )
		);
	}

	/**
	 * Will return if a booking is affected by a total breakdown ( the booked item is not usable ).
	 * Because since #866 not all total breakdowns are cancelled, we need a way to make sure that the user
	 * will not be notified about their upcoming bookings or asked to give feedback because they might not have used the item.
	 *
	 * @return bool true if booking is affected by a total breakdown
	 */
	public function hasTotalBreakdown(): bool {
		$itemID       = $this->getItem()->ID;
		$locationID   = $this->getLocation()->ID;
		$restrictions = \CommonsBooking\Repository\Restriction::get(
			[ $locationID ],
			[ $itemID ],
			null,
			true,
			$this->getStartDate()
		);
		foreach ( $restrictions as $restriction ) {
			if (
				$restriction->getType() == Restriction::TYPE_REPAIR ) {
				$bookings = \CommonsBooking\Repository\Booking::getByRestriction( $restriction );
				foreach ( $bookings as $booking ) {
					if ( $booking->ID == $this->ID ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Returns formatted user info based on the template field in settings -> templates
	 *
	 * @return mixed
	 */
	public static function getFormattedUserInfo() {
		return commonsbooking_parse_template(
			Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'user_details_template' )
		);
	}

	/**
	 * Returns formatted backend edit link of current booking
	 *
	 * @return string
	 */
	public function getFormattedEditLink() {
		return '<a href=" ' . get_edit_post_link( $this->ID ) . '"> Booking #' . $this->ID . ' : ' . $this->formattedBookingDate() . ' | User: ' . $this->getUserData()->user_nicename . '</a>';
	}

	/**
	 * Will return a location where an orphaned booking can be moved to. This is
	 * the new location of the timeframe the booking was previously attached to.
	 *
	 * @return ?Location
	 */
	public function getMoveableLocation(): ?Location {
		if ( ! $this->isOrphaned() ) {
			return null;
		}
		$attachedTFMeta = intval( get_post_meta( $this->ID, self::META_LAST_TIMEFRAME, true ) );
		if ( empty( $attachedTFMeta ) ) {
			throw new Exception( 'No attached timeframe found for orphaned booking.' );
		}
		$attachedTF = new \CommonsBooking\Model\Timeframe( $attachedTFMeta );
		return $attachedTF->getLocation();
	}

	/**
	 * Updates internal booking comment by adding new comment in a new line
	 *
	 * @param  string $comment
	 * @param  int    $userID
	 * @return void
	 */
	public function appendToInternalComment( string $comment, int $userID ) {
		$existing_comment = $this->getMeta( 'internal-comment' );
		$dateTimeInfo     = current_datetime()->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		$meta_string      = $dateTimeInfo . ' / ' . get_the_author_meta( 'user_login', $userID ) . "\n";
		$new_comment      = $existing_comment . "\n" . $meta_string . $comment . "\n--------------------";
		update_post_meta( $this->ID, 'internal-comment', $new_comment );
	}


	/**
	 * Checks wp post filed if booking status is confirmed
	 *
	 * @return bool
	 */
	public function isConfirmed(): bool {
		return $this->post_status === 'confirmed';
	}

	/**
	 * Checks wp post field if booking status is unconfirmed
	 *
	 * @return bool
	 */
	public function isUnconfirmed(): bool {
		return $this->post_status === 'unconfirmed';
	}

	/**
	 * Sums the total duration of an array of individual bookings
	 *
	 * @since 2.9.0
	 *
	 * @param \CommonsBooking\Model\Booking[] $bookings
	 * @return int
	 */
	public static function getTotalDuration( array $bookings ): int {
		$totalDurationOfDays = 0;
		foreach ( $bookings as $booking ) {
			$totalDurationOfDays += $booking->getDuration();
		}
		return $totalDurationOfDays;
	}

	/**
	 * Filters an array of Bookings on the condition whether they apply to given terms
	 * Will return null if no booking in the array matches the terms
	 *
	 * Checks if it has actually received $terms and not an empty variable so that it can just return all bookings if not checking against any terms
	 *
	 * @since 2.9.0
	 *
	 * @param Booking[]                    $bookings The booking to check
	 * @param int|string|array<int|string> $terms The terms that the bookings are filtered against
	 * @return Booking[]|null
	 */
	public static function filterTermsApply( array $bookings, $terms ): ?array {
		if ( ! empty( $terms ) ) {
			$filteredBookingsArray = array_filter(
				$bookings,
				fn( Booking $booking ) => $booking->termsApply( $terms )
			);
			if ( ! empty( $filteredBookingsArray ) ) {
				return $filteredBookingsArray;
			} else {
				return null;
			}
		} else {
			return $bookings;
		}
	}

	/**
	 * Filters an array of bookings on the condition if they belong to a specific user
	 * Will return null if none of the bookings apply to the specified user
	 *
	 * @since 2.9.0
	 *
	 * @param Booking[] $bookings
	 * @param WP_User   $user
	 * @return Booking[]|null
	 */
	public static function filterForUser( array $bookings, WP_User $user ): ?array {
		return array_filter(
			$bookings,
			fn( Booking $booking ) => $booking->getUserData()->ID == $user->ID
		);
	}


	/**
	 * Will get the status of a booking as a human-readable string
	 *
	 * @return string
	 */
	public function getStatus(): string {
		if ( $this->isConfirmed() ) {
			return __( 'Confirmed', 'commonsbooking' );
		} elseif ( $this->isUnconfirmed() ) {
			return __( 'Unconfirmed', 'commonsbooking' );
		} elseif ( $this->isCancelled() ) {
			return __( 'Cancelled', 'commonsbooking' );
		} else {
			return __( 'Unknown', 'commonsbooking' );
		}
	}
}
