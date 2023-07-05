<?php


namespace CommonsBooking\Model;

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

class Booking extends \CommonsBooking\Model\Timeframe {

	const START_TIMEFRAME_GRIDSIZE = 'start-timeframe-gridsize';

	const END_TIMEFRAME_GRIDSIZE = 'end-timeframe-gridsize';

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
	 * Returns the booking code.
     *
	 * @return mixed
	 */
	public function getBookingCode() {
		return $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'bookingcode' );
	}

	/**
	 * Sets post_status to canceled.
	 */
	public function cancel() : void {

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
	 */
	protected function sendCancellationMail() {
		$booking_msg = new BookingMessage( $this->getPost()->ID, 'canceled' );
		$booking_msg->triggerMail();
	}

	/**
	 * Returns rendered booking code for using in email-template (booking confirmation mail)
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
			$htmloutput = '<br>' . sprintf( commonsbooking_sanitizeHTML( __( 'Your booking code is: %s', 'commonsbooking' ) ), $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'bookingcode' ) ) . '<br>';
		}

		return $htmloutput;
	}

	/**
	 * Returns true if booking codes shall be shown in frontend.
     *
	 * @return bool
	 */
	public function showBookingCodes(): bool {
		return $this->getMeta( 'show-booking-codes' ) === 'on';
	}

	/**
	 * Returns suitable bookable Timeframe for booking.
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
	 * Assings relevant meta fields from related bookable timeframe to booking.
     *
	 * @throws Exception
	 */
	public function assignBookableTimeframeFields() {
		$timeframe = $this->getBookableTimeFrame();
		if ( $timeframe ) {
			$neededMetaFields = [
				'full-day',
				'grid',
				'start-time',
				'end-time',
				'show-booking-codes',
				'timeframe-max-days',
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
			$bookingCode = BookingCodes::getCode(
				$timeframe->ID,
				$this->getItem()->ID,
				$this->getLocation()->ID,
				date( 'Y-m-d', $this->getStartDate() )
			);

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
	 * Gets the booking that is directly previous to this booking at the same place / location.
	 *
	 * @return Booking|null
	 */
	public function getPreviousAdjacent(): ?Booking {
		$adjacentBookings = \CommonsBooking\Repository\Booking::getByTimerange(
			$this->getStartDateDateTime()->modify( "-2 minute" )->getTimestamp(),
			$this->getStartDateDateTime()->modify( "-1 minute" )->getTimestamp(),
			$this->getLocationID(),
			$this->getItemID(),
			[],
			[ 'confirmed' ]
		);
		if (count($adjacentBookings) == 1){
			return reset($adjacentBookings);
		}
		elseif (count($adjacentBookings) > 1){
			throw new Exception("Overlapping booking detected.");
		}
		else {
			return null;
		}
	}

	/**
	 * Gets the booking that is directly following this booking at the same place / location.
	 *
	 * @return Booking|null
	 */
	public function getFollowingAdjacent(): ?Booking {
		$adjacentBookings = \CommonsBooking\Repository\Booking::getByTimerange(
			$this->getEndDateDateTime()->modify( "+1 minute" )->getTimestamp(),
			$this->getEndDateDateTime()->modify( "+2 minute" )->getTimestamp(),
			$this->getLocationID(),
			$this->getItemID(),
			[],
			[ 'confirmed' ]
		);
		if (count($adjacentBookings) == 1){
			return reset($adjacentBookings);
		}
		elseif (count($adjacentBookings) > 1){
			throw new Exception("Overlapping booking detected.");
		}
		else {
			return null;
		}
	}

	/**
	 * Gets the bookings directly adjacent to the current booking (with same item / location of course)
	 *
	 * @return array
	 */
	public function getAdjacentBookings(): ?array {
		$previousAdjacent = $this->getPreviousAdjacent();
		$followingAdjacent = $this->getFollowingAdjacent();
		return array_filter([$previousAdjacent, $followingAdjacent]);
	}

	/**
	 * Get the bookings directly adjacent to the current users booking. Limited to a specific user to reduce load.
	 *
	 * @param WP_User $user
	 *
	 * @return array
	 */
	public function getBookingChain(WP_User $user){
		$bookingChain = [];
		$previousBooking = $this->getPreviousAdjacent();
		if ($previousBooking && $previousBooking->getUserData()->ID != $user->ID){
			$previousBooking = null;
		}
		$followingBooking = $this->getFollowingAdjacent();
		if ($followingBooking && $followingBooking->getUserData()->ID != $user->ID){
			$followingBooking = null;
		}
		while ($previousBooking != null){
			$bookingChain[] = $previousBooking;
			$previousBooking = $previousBooking->getPreviousAdjacent();
			if ($previousBooking && $previousBooking->getUserData()->ID != $user->ID){
				$previousBooking = null;
			}
		}
		while ($followingBooking != null){
			$bookingChain[] = $followingBooking;
			$followingBooking = $followingBooking->getFollowingAdjacent();
			if ($followingBooking && $followingBooking->getUserData()->ID != $user->ID){
				$followingBooking = null;
			}
		}
		return $bookingChain;
	}

	/**
	 * Returns time from repetition-[start/end] field
	 *
	 * @param $fieldName
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
	 * @return ?Location
	 * @throws Exception
	 */
	public function getLocation(): ?Location {
		if ( $post = get_post( $this->getLocationID() ) ) {
			return new Location( $post );
		}

		return null;
	}

	public function getItemID() {
		return $this->getMeta('item-id');
	}

	public function getLocationID(){
		return $this->getMeta('location-id');
	}
	/**
	 * @return string
	 */
	public function formattedBookingDate(): string {
		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );

		$startdate = date_i18n( $date_format, $this->getStartDate() );
		$enddate   = date_i18n( $date_format, $this->getRawEndDate() );

		if ( $startdate === $enddate ) {
			/* translators: %s = date in WordPress defined format */
			return sprintf( sanitize_text_field( __( ' on %s ', 'commonsbooking' ) ), $startdate );
		} else {
			/* translators: %1 = startdate, %2 = enddate in WordPress defined format */
			return sprintf( sanitize_text_field( __( ' from %1$s until %2$s ', 'commonsbooking' ) ), $startdate, $enddate );
		}
	}


	/**
	 * pickupDatetime
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

		$date_start = date_i18n( $date_format, $repetitionStart );
		$time_start = date_i18n( $time_format, $repetitionStart );
		$time_end   = date_i18n( $time_format, $repetitionStart ); // TODO Ist das korrekt?

		if ( $this->isFullDay() ) {
			return $date_start;
		}

		$grid = $this->getGrid();

		if ( $grid === 0 ) { // if grid is set to slot duration
			// If we have the grid size, we use it to calculate right time end
			$timeframeGridSize = $this->getMeta( self::START_TIMEFRAME_GRIDSIZE );
			if ( $timeframeGridSize ) {
				$grid = $timeframeGridSize;
			}
		}

		if ( $grid > 0 ) { // if grid is set to hourly (grid = 1) or a multiple of an hour
			$time_end = date_i18n( $time_format, $repetitionStart + ( 60 * 60 * $grid ) );
		}

		return $date_start . ' ' . $time_start . ' - ' . $time_end;
	}

	/**
	 * returnDatetime
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
		$time_start = date_i18n( $time_format, strtotime( $this->getStartTime() ) );

		if ( $this->isFullDay() ) {
			return $date_end;
		}

		$grid = $this->getGrid();

		if ( $grid === 0 ) { // if grid is set to slot duration
			// If we have the grid size, we use it to calculate right time start
			$timeframeGridSize = $this->getMeta( self::END_TIMEFRAME_GRIDSIZE );
			if ( $timeframeGridSize ) {
				$grid = $timeframeGridSize;
			}
		}

		if ( $grid > 0 ) { // if grid is set to hourly (grid = 1) or a multiple of an hour
			$time_start = date_i18n( $time_format, $this->getRawEndDate() + 1 - ( 60 * 60 * $grid ) );
		}

		return $date_end . ' ' . $time_start . ' - ' . $time_end;
	}

	public function getStartDate() : int {
		return intval( $this->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ) );
	}

	public function getEndDate() : int{
		return intval($this->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_END ));
	}

	/**
	 * Returns comment text.
     *
	 * @return string
	 */
	public function returnComment(): string {
		return commonsbooking_sanitizeHTML( $this->getMeta( 'comment' ) );
	}

	/**
	 * show booking notice
	 *
	 * @return string
	 */
	public function bookingNotice(): ?string {

		$currentStatus    = $this->post->post_status;
		$cancellationTime = $this->getMeta( 'cancellation_time' );

  		if ( $currentStatus == 'unconfirmed' ) {
            // transient is set in \Model\Booking->handleFormRequest if overlapping booking exists
            if ( get_transient( 'commonsbooking_overlappingBooking_' . $this->ID ) ) {
                $noticeText = get_transient( 'commonsbooking_overlappingBooking_' . $this->ID ) . ' ' . $this->ID . commonsbooking_sanitizeHTML( __(
                    '<h1 style="color:red">Notice:</h1> <p>We are sorry. Something went wrong. This booking could not be confirmed because there is another overlapping booking.<br>
                    Please click the "Cancel"-Button and select another booking period.</p>
                    <p>Normally, the booking system ensures that no overlapping bookings can be created. If you think there is a bug, please contact us.</p> 
                ', 'commonsbooking' ) );

                delete_transient( 'commonsbooking_overlappingBooking_' . $this->ID );
            } else {
                $noticeText = commonsbooking_sanitizeHTML( __( 'Please check your booking and click confirm booking', 'commonsbooking' ) );
            }
		} elseif ( $this->isConfirmed() ) {
			$noticeText = commonsbooking_sanitizeHTML( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'booking-confirmed-notice' ) );
		}

		if ( $this->isCancelled() ) {
            if ( $cancellationTime ) {
                $cancellationTimeFormatted = Helper::FormattedDateTime( $cancellationTime );
			    $noticeText                = sprintf( commonsbooking_sanitizeHTML( __( 'Your booking has been canceled at %s.', 'commonsbooking' ) ), $cancellationTimeFormatted );
            } else {
                $noticeText = commonsbooking_sanitizeHTML( __( 'Your booking has been canceled', 'commonsbooking' ) );
            }
		}

		if ( isset( $noticeText ) ) {
			return sprintf( '<div class="cb-notice cb-booking-notice cb-status-%s">%s</div>', $currentStatus, $noticeText );
		}

		return null;
	}

	/**
	 * Return HTML Link to booking
     *
	 * @TODO: optimize booking link to support different permalink settings or set individual slug (e.g. booking instead of cb_timeframe)
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
	 * Returns true when booking is cancelled
	 *
	 * @return bool
	 */
	public function isCancelled(): bool {
		return $this->post_status === 'canceled';
	}

	/**
	 * Checks if the given user / current user is administrator of item / location or the website and therefore enjoys special booking rights
	 *
	 * @param WP_User|null $user
	 *
	 * @return bool
	 */
	public function isUserPrivileged(WP_User $user = null): bool {
		$user ??= $this->getUserData();

		$itemAdmin = commonsbooking_isUserAllowedToEdit($this->getItem(),$user);
		$locationAdmin = commonsbooking_isUserAllowedToEdit($this->getLocation(),$user);
		return ($itemAdmin || $locationAdmin);
	}

	/**
	 * Returns true when booking has ended
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
	 * @param int|array|string $term
	 *
	 * @return bool
	 */
	public function termsApply( $term ): bool {
		try {
			$item = $this->getItem();
			$location = $this->getLocation();
		}
		catch ( Exception $e ) {
			//terms are not applicable if either location or item is not found
			return false;
		}
		$isInItemCat = has_term( $term, \CommonsBooking\Wordpress\CustomPostType\Item::$postType . 's_category', $item->getPost() );
		$isInLocationCat = has_term( $term, \CommonsBooking\Wordpress\CustomPostType\Location::$postType . 's_category', $location->getPost() );
		return ( $isInItemCat || $isInLocationCat);
	}

	/**
	 * Gets the length of a booking in days.
	 * The behaviour of this function depends on the type of booking.
	 * When a booking is confirmed or unconfirmed, it will return the whole amount of days in the booking interval.
	 * When a booking is cancelled, it will only return the amount of days the item has been "used" (from start to cancellation).
	 *
	 * A day counts as "used", when it has started. That means if a booking is cancelled at 00:01, the day is still counted.
	 * @return int
	 */
	public function getLength(): int{
		$interval = null;
		if ( $this->isUnconfirmed() || $this->isConfirmed() ) {
			$interval = $this->getStartDateDateTime()->diff($this->getEndDateDateTime()->modify("+5 min"));
		}
		elseif ($this->isCancelled()){
			$interval = $this->getStartDateDateTime()->diff($this->getCancellationDateDateTime());
		}
		else {
			//Booking has no valid status
			return 0;
		}
		if ($interval === null){
			//no interval created
			return 0;
		}
		//get the fully completed days and also count a just started day as one day
		$days = $interval->d;
		//when we have already moved into the next day for more one hour,it is counted as another day even if it is not completed
		if ($interval->h > 0){
			$days++;
		}
		return $days;
	}

	/**
	 * Will get the DateTime object of the cancellation date.
	 * The cancellation date will be saved as postmeta when a booking is cancelled.
	 * Will return null when the booking is not cancelled.
	 * @return DateTime
	 */
	public function getCancellationDateDateTime(): ?DateTime {
		if ( ! $this->isCancelled() ) {
			return null;
		}
		$cancellationTimestamp = $this->getMeta( 'cancellation_time' );
		return Wordpress::getUTCDateTimeByTimestamp( $cancellationTimestamp );
	}

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
     * Returns formatted user info based on the template field in settings -> templates
     *
     * @return void
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
     * Updates internal booking comment by adding new comment in a new line
     *
     * @param  string $comment
     * @param  int $userID
     * @return void
     */
    public function appendToInternalComment( string $comment, int $userID ) {
        $existing_comment = $this->getMeta( 'internal-comment' );
        $dateTimeInfo = current_datetime()->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
        $meta_string = $dateTimeInfo . ' / ' . get_the_author_meta( 'user_login', $userID ) . "\n";
        $new_comment = $existing_comment . "\n" . $meta_string . $comment . "\n--------------------";
        return update_post_meta( $this->ID, 'internal-comment', $new_comment );
    }


	/**
	 * Checks wp post filed if booking status is confirmed
	 *
	 * @return bool
	 */
	public function isConfirmed() : bool {
		return $this->post_status === 'confirmed';
	}

	/**
	 * Checks wp post field if booking status is unconfirmed
	 *
	 * @return bool
	 */
	public function isUnconfirmed() : bool {
		return $this->post_status === 'unconfirmed';
	}

	/**
	 * Gets the total length (days) of an array of bookings
	 *
	 * @param   \CommonsBooking\Model\Booking[]  $bookings
	 *
	 * @return void
	 */
	public static function getTotalLength ( array $bookings ): int {
		$lengthDays = 0;
		foreach ($bookings as $booking){
			$lengthDays += $booking->getLength();
		}
		return $lengthDays;
	}

	/**
	 * Filters an array of Bookings on the condition weather they apply to term(s) or not
	 * Will return null if no booking in the array matches the terms
	 *
	 * Checks if it has actually received $terms and not an empty variable so that it can just return all bookings if not checking against any terms
	 *
	 * @param   \CommonsBooking\Model\Booking[]  $bookings
	 * @param                                    $terms
	 *
	 * @return array|null
	 */
	public static function filterTermsApply ( array $bookings,  $terms): ?array {
		if ( ! empty($terms) ){
			$filteredBookingsArray = array_filter($bookings,
				fn( Booking $booking ) => $booking->termsApply($terms)
			);
			if ( ! empty ($filteredBookingsArray) ){
				return $filteredBookingsArray;
			}
			else {
				return null;
			}
		}
		else {
			return $bookings;
		}
	}

	/**
	 * Filters an array of bookings on the condition if they belong to a specific user
	 * Will return null if none of the bookings apply to the specified user
	 * @param   array     $bookings
	 * @param   \WP_User  $user
	 *
	 * @return array|null
	 */
	public static function filterForUser ( array $bookings, WP_User $user): ?array {
		 return array_filter( $bookings,
			fn( Booking $booking ) => $booking->getUserData()->ID == $user->ID
		);
	}

}
