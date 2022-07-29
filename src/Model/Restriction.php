<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Messages\RestrictionMessage;
use DateTime;

class Restriction extends CustomPost {

	const TYPE_REPAIR = 'repair';

	const TYPE_HINT = 'hint';

	const STATE_NONE = 'none';

	const STATE_ACTIVE = 'active';

	const STATE_SOLVED = 'solved';

	const META_HINT = 'restriction-hint';

	const META_START = 'restriction-start';

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
	 * @param null $endDateString
	 *
	 * @return DateTime
	 */
	public function getEndTimeDateTime( $endDateString = null ): DateTime {
		$endTimeString = $this->getMeta( self::META_END );
		$endDate       = Wordpress::getUTCDateTime();

		if ( $endTimeString ) {
			$endTime = Wordpress::getUTCDateTime();
			$endTime->setTimestamp( $endTimeString );
			$endDate->setTime( $endTime->format( 'H' ), $endTime->format( 'i' ) );
		} else {
			$endDate->setTimestamp( $endDateString );
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
	 * Returns true if there is set an enddate
     *
	 * @return bool
	 */
	public function hasEnddate() {
		return $this->getMeta( self::META_END ) !== '';
	}

	/**
	 * Returns end timestamp. Of no enddate is set it retunrs a date far in the future.
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
     *
	 * @return mixed
	 */
	public function getHint() {
		return $this->getMeta( self::META_HINT );
	}

	/**
	 * Returns nicely formatted start datetime.
     *
	 * @return string
	 */
	public function getFormattedStartDateTime() {
		// TODO timeformat should be configurable
		return $this->getStartTimeDateTime()->format( 'd.m.Y H:i' );
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @return DateTime
	 */
	public function getStartTimeDateTime(): DateTime {
		$startDateString = $this->getMeta( self::META_START );
		$startDate       = Wordpress::getUTCDateTime();
		$startDate->setTimestamp( $startDateString );

		return $startDate;
	}

	/**
	 * Returns nicely formatted end datetime.
     *
	 * @return string
	 */
	public function getFormattedEndDateTime() {
		// TODO timeformat should be configurable
		return $this->getEndDateDateTime()->format( 'd.m.Y H:i' );
	}

	/**
	 * Returns end-date \DateTime.
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
	 * Returns item name.
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
	 * Returns itemId
     *
	 * @return mixed
	 */
	public function getItemId() {
		return $this->getMeta( self::META_ITEM_ID );
	}

	/**
	 * Returns location name.
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
	 * Returns location id.
     *
	 * @return mixed
	 */
	public function getLocationId() {
		return $this->getMeta( self::META_LOCATION_ID );
	}

	/**
	 * Apply restriction workflow.
	 */
	public function apply() {
		// Check if this is an active restriction
		if ( $this->isActive() ) {
			$bookings = \CommonsBooking\Repository\Booking::getByRestriction( $this );
			if ( $bookings ) {
				if ( $this->isActive() && $this->getType() == self::TYPE_REPAIR ) {
					$this->cancelBookings( $bookings );
				}
				$this->sendRestrictionMails( $bookings );
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
     *
	 * @return mixed
	 */
	public function getType() {
		return $this->getMeta( self::META_TYPE );
	}

	/**
	 * Cancels bookings if restriction is active and of type repair.
	 *
	 * @param Booking[] $bookings booking post objects.
	 */
	protected function cancelBookings( $bookings ) {
		foreach ( $bookings as $booking ) {
			$booking->cancel();
		}
	}

	/**
	 * Send restriction mails regarding item/location admins and booked timeslots.
	 *
	 * @param Booking[] $bookings booking post objects.
	 */
	protected function sendRestrictionMails( $bookings ) {
		$userIds = [];

		foreach ( $bookings as $booking ) {
			// User IDs from booking
			$userIds[] = $booking->getUserData()->ID;
        }

        // Delete duplicate user-ids so that restriction mail will only be send once to each user
        $userIds = array_unique( $userIds );

        foreach ( $userIds as $userId ) {
            $hintMail = new RestrictionMessage( $this, get_userdata( $userId ), $booking, $this->getType() );
            $hintMail->triggerMail();
        }
    }

	/**
     * Returns true if a restriction status in cancelled
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
