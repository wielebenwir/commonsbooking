<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Messages\RestrictionMessage;
use DateTime;

/**
 * Timeframe for restricting access to an item.
 * This is the logical wrapper for the restriction custom post type.
 *
 * Retrieve restrictions from the database using the @see \CommonsBooking\Repository\Restriction class.
 * Additionally, all the public functions in this class can be called using Template Tags.
 *
 * Note: Timeframes are date intervals, with a start date and either an end date or no end date (which leaves the interval open).
 *       This should be kept in mind when processing/rendering information in user templates.
 */
class Restriction extends CustomPost {

	/**
	 * Referred to in the frontend as "total breakdown".
	 * This means, that the item is not available for booking and that all corresponding bookings will be cancelled.
	 */
	const TYPE_REPAIR = 'repair';

	/**
	 * This means, that the item is still bookable, but that users will be notified about the restriction.
	 * This is used for example when the item is only available in a limited manner.
	 */
	const TYPE_HINT = 'hint';

	/**
	 * This is unused, maybe @depreacted ?
	 */
	const STATE_NONE = 'none';

	/**
	 * This is an active restriction that will be displayed to the user.
	 */
	const STATE_ACTIVE = 'active';

	/**
	 * This is a solved restriction that will not be displayed to the user.
	 */
	const STATE_SOLVED = 'solved';

	/**
	 * The meta-field that holds the message containing information about the restriction for the user.
	 */
	const META_HINT = 'restriction-hint';

	/**
	 * 1. Used with input from CMB2-Field type text_datetime_timestamp
	 *
	 * type of field int = local unix timestamp (php settings?)
	 */
	const META_START = 'restriction-start';

	/**
	 * 1. Used with input from CMB2-Field type text_datetime_timestamp
	 *
	 * type of field int = local unix timestamp (
	 */
	const META_END = 'restriction-end';

	const META_TYPE = 'restriction-type';

	const META_STATE = 'restriction-state';

	public const META_LOCATION_ID = 'restriction-location-id';

	public const META_ITEM_ID = 'restriction-item-id';

	const META_SENT = 'restriction-sent';

	const NO_END_TIMESTAMP = 3000000000;

	protected $active;

	protected $canceled;

	/**
	 * Returns post id, for array_unique.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return strval( $this->post->ID );
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @param int|string|null $endDateString numeric string
	 *
	 * @return DateTime
	 */
	public function getEndTimeDateTime( $endDateString = null ): DateTime {
		$endTimeString = $this->getMeta( self::META_END );
		$endDate       = Wordpress::getUTCDateTime();

		if ( $endTimeString ) {
			$endTime = Wordpress::getUTCDateTime();
			$endTime->setTimestamp( (int) $endTimeString );
			$endDate->setTime( (int) $endTime->format( 'H' ), (int) $endTime->format( 'i' ) );
		} else {
			$endDate->setTimestamp( (int) $endDateString );
		}

		return $endDate;
	}

	/**
	 * @return int Timestamp
	 */
	public function getStartDate(): int {
		return intval( $this->getMeta( self::META_START ) );
	}

	/**
	 * Returns true if the restriction has an enddate.
	 *
	 * Used in timeframe-calendar.php template
	 *
	 * @return bool
	 */
	public function hasEnddate() {
		return $this->getMeta( self::META_END ) !== '';
	}

	/**
	 * Returns end timestamp. Of no end-date is set it returns a date far in the future.
	 *
	 * @return int Timestamp
	 */
	public function getEndDate(): int {
		// Set a far in the future date if enddate isn't set
		$metaEndDate = $this->getMeta( self::META_END ) !== '' ? $this->getMeta( self::META_END ) : self::NO_END_TIMESTAMP;

		return intval( $metaEndDate );
	}

	/**
	 * Returns true if restriction isn't active.
	 *
	 * @return bool
	 */
	public function isOverBookable(): bool {
		return ! $this->isActive();
	}


	/**
	 * Returns true if restriction is active
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		if ( $this->active === null ) {
			$this->active = $this->getMeta( self::META_STATE ) === self::STATE_ACTIVE ?: false;
		}

		return $this->active;
	}

	/**
	 * Returns true if restriction ist active.
	 * TODO this function seems unused in restriction context. Check if it can be removed @markus-mw
	 *
	 * @return bool
	 */
	public function isLocked(): bool {
		return $this->isActive();
	}

	/**
	 * Returns restriction hint.
	 * The restriction hint is the little message explaining why the item is restricted.
	 *
	 * @return mixed
	 */
	public function getHint() {
		return $this->getMeta( self::META_HINT );
	}

	/**
	 * Returns nicely formatted start datetime.
	 *
	 * @return string if META_START is not null.
	 */
	public function getFormattedStartDateTime() {
		return Helper::FormattedDateTime( $this->getStartTimeDateTime()->getTimestamp() );
	}

	/**
	 * Returns start-time \DateTime.
	 * This will get a DateTime object with the UTC timezone attached but the timestamp will still be in the local timezone.
	 *
	 * @return DateTime
	 */
	public function getStartTimeDateTime(): DateTime {
		$startDateString = $this->getMeta( self::META_START );
		$startDate       = Wordpress::getUTCDateTime();
		$startDate->setTimestamp( (int) $startDateString );

		return $startDate;
	}

	/**
	 * Returns nicely formatted end datetime.
	 *
	 * @return string if META_END is not null.
	 */
	public function getFormattedEndDateTime() {
		return Helper::FormattedDateTime( $this->getEndDateDateTime()->getTimestamp() );
	}

	/**
	 * Returns end-date \DateTime.
	 * This will get a DateTime object with the UTC timezone attached but the timestamp will still be in the local timezone.
	 *
	 * @return DateTime
	 */
	public function getEndDateDateTime(): DateTime {
		$endDateString = intval( $this->getMeta( self::META_END ) );
		$endDate       = Wordpress::getUTCDateTime();
		$endDate->setTimestamp( $endDateString );

		return $endDate;
	}

	/**
	 * Returns item name for the item that is restricted.
	 *
	 * @return string
	 */
	public function getItemName(): string {
		$itemName = esc_html__( 'Not set', 'commonsbooking' );
		if ( $this->getItemId() ) {
			$item     = get_post( $this->getItemId() );
			$itemName = $item->post_title;
		}

		return $itemName;
	}

	/**
	 * Returns itemId for the item that is restricted.
	 *
	 * @return mixed
	 */
	public function getItemId() {
		return $this->getMeta( self::META_ITEM_ID );
	}

	/**
	 * Returns location name for the location that the restricted item is in.
	 *
	 * @return string
	 */
	public function getLocationName(): string {
		$locationName = esc_html__( 'Not set', 'commonsbooking' );
		if ( $this->getLocationId() ) {
			$location     = get_post( $this->getLocationId() );
			$locationName = $location->post_title;
		}

		return $locationName;
	}

	/**
	 * Returns location id for the location that the restricted item is in.
	 *
	 * @return mixed
	 */
	public function getLocationId() {
		return $this->getMeta( self::META_LOCATION_ID );
	}

	/**
	 * Apply restriction workflow.
	 * Will cancel the bookings if restriction is active and type is total breakdown.
	 */
	public function apply() {
		// Check if this is an active restriction
		if ( $this->isActive() ) {
			$bookings = \CommonsBooking\Repository\Booking::getByRestriction( $this );
			if ( $bookings ) {
				// send restriction mails to all affected bookings
				$this->sendRestrictionMails( $bookings );

				$userDisabledBookingCancellationOnTotalBreakdown = \CommonsBooking\Settings\Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-no-cancel-on-total-breakdown' ) == 'on';
				// cancel all affected booking
				if ( ! $userDisabledBookingCancellationOnTotalBreakdown
					&& $this->getType() === self::TYPE_REPAIR ) {
					$this->cancelBookings( $bookings );
				}
			}
		}

		// Check if this is a canceled/solved restriction
		if ( $this->isCancelled() ) {
			$canceledBookings = \CommonsBooking\Repository\Booking::getByRestriction( $this );
			if ( $canceledBookings ) {
				$this->sendRestrictionMails( $canceledBookings );
			}
		}
	}

	/**
	 * Returns restriction type.
	 * We currently have two types: hint and repair.
	 * They can be differentiated using the constants TYPE_HINT and TYPE_REPAIR.
	 *
	 * @return mixed
	 */
	public function getType() {
		return $this->getMeta( self::META_TYPE );
	}

	/**
	 * Will cancel all bookings that belong to this restriction.
	 *
	 * @param Booking[] $bookings booking post objects.
	 */
	protected function cancelBookings( $bookings ) {
		foreach ( $bookings as $booking ) {
			$booking->cancel();
		}
	}

	/**
	 * Send information about the restriction to all affected users.
	 * Currently, this will notify a user multiple times if he has multiple bookings that are affected by the restriction.
	 * Trying to implement a better solution, where each user would only get one mail, led to the problem that the booking link in the mail no longer made sense.
	 * Because the booking link is different for multiple bookings of the same user. So we would have to send multiple links to the same user.
	 *
	 * @param Booking[] $bookings booking post objects.
	 */
	protected function sendRestrictionMails( $bookings ) {
		foreach ( $bookings as $key => $booking ) {
			// get User ID from booking
			$userId = $booking->getUserData()->ID;

			// checks if this is the first booking that is processed
			$firstMessage = ( $key === array_key_first( $bookings ) );

			// send restriction message for each booking
			$hintMail = new RestrictionMessage( $this, get_userdata( $userId ), $booking, $this->getType(), $firstMessage );
			$hintMail->triggerMail();
		}
	}

	/**
	 * Returns true if a restriction status in cancelled.
	 * Maybe it would make more sense to create an isActive() method and use that instead.
	 *
	 * @return bool
	 */
	public function isCancelled(): bool {
		if ( $this->canceled === null ) {
			$this->canceled = $this->getMeta( self::META_STATE ) === self::STATE_SOLVED ?: false;
		}

		return $this->canceled;
	}
}
