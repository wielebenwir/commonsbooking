<?php

namespace CommonsBooking\Model;

use Exception;

/**
 * Class Timeframe
 * @package CommonsBooking\Model
 */
class Timeframe extends CustomPost {
	/**
	 * Error type id.
	 */
	public const ERROR_TYPE = "timeframeValidationFailed";

	public const REPETITION_START = "repetition-start";

	public const REPETITION_END = "repetition-end";

	/**
	 * Return residence in a human readable format
	 *
	 * "From xx.xx.",  "Until xx.xx.", "From xx.xx. until xx.xx.", "no longer available"
	 *
	 * @return string
	 */
	public function formattedBookableDate() {
		$startDate = $this->getStartDate() ? $this->getStartDate() : 0;
		$endDate   = $this->getEndDate() ? $this->getEndDate() : 0;

		return self::formatBookableDate( $startDate, $endDate );
	}

	/**
	 * Return Start (repetition) date
	 *
	 * @return string
	 */
	public function getStartDate() {
		$startDate = $this->getMeta( self::REPETITION_START );

		if ( (string) intval( $startDate ) !== $startDate ) {
			$startDate = strtotime( $startDate );
		}

		return $startDate;
	}

	/**
	 * Return End (repetition) date
	 *
	 * @return string
	 */
	public function getEndDate() {
		$endDate = $this->getMeta( self::REPETITION_END );
		if ( (string) intval( $endDate ) !== $endDate ) {
			$endDate = strtotime( $endDate );
		}

		return $endDate;
	}

	/**
	 * Returns latest possible booking date as timestamp.
	 *
	 * @return string
	 */
	public function getLatestPossibleBookingDateTimestamp() {
		// if meta-value not set we define 90 days as default value
		$advanceBookingDays = $this->getMeta( 'timeframe-advance-booking-days' ) ?: '90';
		// we subtract one day to reflect the current day in calculation
		$advanceBookingDays--;

		return strtotime( "+ ". $advanceBookingDays . " days", time() );
	}

	/**
	 * Returns true, if the start date is earlier and the end date later than the latest possible booking date.
	 * @return bool
	 */
	public function isBookable() {
		$startDateTimestamp = $this->getStartDate();
		$endDateTimestamp = $this->getEndDate();
		$latestPossibleBookingDateTimestamp = $this->getLatestPossibleBookingDateTimestamp();
		return
			($startDateTimestamp <= $latestPossibleBookingDateTimestamp) &&
			($endDateTimestamp >= $latestPossibleBookingDateTimestamp);
	}

	/**
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return string
	 */
	public static function formatBookableDate( $startDate, $endDate ) {
		$format = self::getDateFormat();
		$today  = strtotime( 'now' );

		$startDateFormatted = date_i18n( $format, $startDate );
		$endDateFormatted   = date_i18n( $format, $endDate );

		$label           = commonsbooking_sanitizeHTML( __( 'Available here', 'commonsbooking' ) );
		$availableString = '';

		if ( $startDate !== 0 && $endDate !== 0 && $startDate == $endDate ) { // available only one day
			/* translators: %s = date in wordpress defined format */
			$availableString = sprintf( commonsbooking_sanitizeHTML( __( 'on %s', 'commonsbooking' ) ), $startDateFormatted );
		} elseif ( $startDate > 0 && ( $endDate == 0 ) ) { // start but no end date
			if ( $startDate > $today ) { // start is in the future
				/* translators: %s = date in wordpress defined format */
				$availableString = sprintf( commonsbooking_sanitizeHTML( __( 'from %s', 'commonsbooking' ) ),
					$startDateFormatted );
			} else { // start has passed, no end date, probably a fixed location
				$availableString = commonsbooking_sanitizeHTML( __( 'permanently', 'commonsbooking' ) );
			}
		} elseif ( $startDate > 0 && $endDate > 0 ) { // start AND end date
			if ( $startDate > $today ) { // start is in the future, with an end date
				/* translators: %1$s = startdate, second %2$s = enddate in wordpress defined format */
				$availableString = sprintf( commonsbooking_sanitizeHTML( __( ' from %1$s until %2$s', 'commonsbooking' ) ),
					$startDateFormatted,
					$endDateFormatted );
			} else { // start has passed, with an end date
				/* translators: %s = enddate in wordpress defined format */
				$availableString = sprintf( commonsbooking_sanitizeHTML( __( ' until %s', 'commonsbooking' ) ),
					$endDateFormatted );
			}
		}

		return $label . ' ' . $availableString;
	}

	/**
	 * Return date format
	 *
	 * @return string
	 */
	public static function getDateFormat() {
		return get_option( 'date_format' );
	}

	/**
	 * Return  time format
	 *
	 * @return string
	 */
	public function getTimeFormat(): string {
		return get_option( 'time_format' );
	}

	/**
	 * Validates if there can be booking codes created for this timeframe.
	 * @return bool
	 */
	public function bookingCodesApplieable(): bool {
		try {
			return $this->getLocation() && $this->getItem() &&
			       $this->getStartDate() && $this->getEndDate() &&
			       $this->getType() == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @return Location
	 * @throws Exception
	 */
	public function getLocation(): ?Location {
		$locationId = $this->getMeta( 'location-id' );
		if ( $locationId ) {
			if ( $post = get_post( $locationId ) ) {
				return new Location( $post );
			}
		}
		return null;
	}

	/**
	 * @return Item
	 * @throws Exception
	 */
	public function getItem(): ?Item {
		$itemId = $this->getMeta( 'item-id' );
		if ( $itemId ) {
			if ( $post = get_post( $itemId ) ) {
				return new Item( $post );
			}
		}
		return null;
	}

	/**
	 * Returns type id
	 * @return mixed
	 */
	public function getType() {
		return $this->getMeta( 'type' );
	}

	/**
	 * Returns true if timeframe is full-day
	 * @return boolean
	 */
	public function isFullDay() {
		return $this->getMeta( 'full-day' ) == 'on';
	}

	/**
	 * Checks if Timeframe is valid.
	 * @return bool
	 * @throws Exception
	 */
	public function isValid() {
		if (
			$this->getType() == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID
		) {
			
			if ( ! $this->getItem() || ! $this->getLocation() ) {
				// if location or item is missing
				set_transient( self::ERROR_TYPE,
					commonsbooking_sanitizeHTML( __( "Item or location is missing. Please set item and location. Timeframe is saved as draft",
						'commonsbooking' ) ),
					45 );

				return false;				
			}
			
			
			if ( ! $this->getStartDate() ) {
				// If there is at least one mandatory parameter missing, we cannot save/publish timeframe.
				set_transient( self::ERROR_TYPE,
					commonsbooking_sanitizeHTML( __( "Startdate is missing. Timeframe is saved as draft. Please enter a start date to publish this timeframe.",
						'commonsbooking' ) ),
					45 );

				return false;
			}

			if (
				$this->getLocation() &&
				$this->getItem() &&
				$this->getStartDate()
			) {
				$postId = $this->ID;

				if ( $this->getStartTime() && ! $this->getEndTime() && ! $this->isFullDay() ) {
					set_transient( self::ERROR_TYPE,
						commonsbooking_sanitizeHTML( __( "A pickup time but no return time has been set. Please set the return time.",
							'commonsbooking' ) ),
						45 );

					return false;
				}

				// Get Timeframes with same location, item and a startdate
				$existingTimeframes = \CommonsBooking\Repository\Timeframe::get(
					[ $this->getLocation()->ID ],
					[ $this->getItem()->ID ],
					[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
					null,
					true
				);

				// filter current timeframe
				$existingTimeframes = array_filter( $existingTimeframes, function ( $timeframe ) use ( $postId ) {
					return $timeframe->ID !== $postId && $timeframe->getStartDate();
				} );

				// Validate against existing other timeframes
				foreach ( $existingTimeframes as $timeframe ) {

					// check if timeframes overlap
					if (
					$this->hasTimeframeDateOverlap( $this, $timeframe )
					) {

						// Compare grid types
						if ( $timeframe->getGrid() != $this->getGrid() ) {
							set_transient( self::ERROR_TYPE,
								/* translators: %1$s = timeframe-ID, %2$s is timeframe post_title */
								sprintf( commonsbooking_sanitizeHTML( __( 'Overlapping bookable timeframes are only allowed to have the same grid. See overlapping timeframe ID: %1$s: %2$s',
									'commonsbooking', 5 ) ), $timeframe->ID, $timeframe->post_title ) );

							return false;
						}

						// Check if different weekdays are set
						if (
							array_key_exists( 'weekdays', $_REQUEST ) &&
							is_array( $_REQUEST['weekdays'] ) &&
							$timeframe->getWeekDays()
						) {
							if ( ! array_intersect( $timeframe->getWeekDays(), $_REQUEST['weekdays'] ) ) {
								return true;
							}
						}

						// Check if in day slots overlap
						if ( ! $this->getMeta( 'full-day' ) && $this->hasTimeframeTimeOverlap( $this, $timeframe ) ) {
							set_transient( self::ERROR_TYPE,
								/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
								sprintf( commonsbooking_sanitizeHTML( __( 'time periods are not allowed to overlap. Please check the other timeframe to avoid overlapping time periods during one specific day. See affected timeframe ID: %1$s: %2$s',
									'commonsbooking', 5 ) ), $timeframe->ID, $timeframe->post_title ) );

							return false;
						}

						// Check if full-day slots overlap
						if ( $this->getMeta( 'full-day' ) ) {
							set_transient( self::ERROR_TYPE,
								/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
								sprintf( commonsbooking_sanitizeHTML( __( 'Date periods are not allowed to overlap. Please check the other timeframe to avoid overlapping Date periods. See affected timeframe ID: %1$s: %2$s',
									'commonsbooking', 5 ) ), $timeframe->ID, $timeframe->post_title ) );

							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Returns start time for day-slots.
	 * @return mixed
	 */
	public function getStartTime() {
		return $this->getMeta( 'start-time' );
	}

	/**
	 * Returns end time for day-slots.
	 * @return mixed
	 */
	public function getEndTime() {
		return $this->getMeta( 'end-time' );
	}

	/**
	 * Checks if timeframes are overlapping in date range.
	 *
	 * @param $timeframe1
	 * @param $timeframe2
	 *
	 * @return bool
	 */
	protected function hasTimeframeDateOverlap( $timeframe1, $timeframe2 ) {
		return
			! $timeframe1->getEndDate() && ! $timeframe2->getEndDate() ||
			(
				$timeframe1->getEndDate() && ! $timeframe2->getEndDate() &&
				$timeframe2->getStartDate() <= $timeframe1->getEndDate() &&
				$timeframe2->getStartDate() >= $timeframe1->getStartDate()
			) ||
			(
				! $timeframe1->getEndDate() && $timeframe2->getEndDate() &&
				$timeframe2->getEndDate() > $timeframe1->getStartDate()
			) ||
			(
				$timeframe1->getEndDate() && $timeframe2->getEndDate() &&
				(
					( $timeframe1->getEndDate() >= $timeframe2->getStartDate() && $timeframe1->getEndDate() <= $timeframe2->getEndDate() ) ||
					( $timeframe2->getEndDate() >= $timeframe1->getStartDate() && $timeframe2->getEndDate() <= $timeframe1->getEndDate() )
				)
			);
	}

	/**
	 * Returns weekdays array.
	 * @return mixed
	 */
	public function getWeekDays() {
		return $this->getMeta( 'weekdays' );
	}

	/**
	 * Returns grit type id
	 * @return mixed
	 */
	public function getGrid() {
		return $this->getMeta( 'grid' );
	}

	/**
	 * Returns grid size in hours.
	 * @return int|null
	 */
	public function getGridSize(): ?int {
		if ( $this->getGrid() == 0 ) {
			$startTime = strtotime( $this->getMeta( 'start-time' ) );
			$endTime   = strtotime( $this->getMeta( 'end-time' ) );

			return intval( round( abs( $endTime - $startTime ) / 3600, 2 ) );
		} elseif ( $this->isFullDay() ) {
			return 24;
		} else {
			return intval( $this->getGrid() );
		}
	}

	/**
	 * Checks if timeframes are overlapping in daily slots.
	 *
	 * @param $timeframe1
	 * @param $timeframe2
	 *
	 * @return bool
	 */
	protected function hasTimeframeTimeOverlap( $timeframe1, $timeframe2 ) {
		return
			! strtotime( $timeframe1->getEndTime() ) && ! strtotime( $timeframe2->getEndTime() ) ||
			(
				strtotime( $timeframe1->getEndTime() ) && ! strtotime( $timeframe2->getEndTime() ) &&
				strtotime( $timeframe2->getStartTime() ) <= strtotime( $timeframe1->getEndTime() ) &&
				strtotime( $timeframe2->getStartTime() ) >= strtotime( $timeframe1->getStartTime() )
			) ||
			(
				! strtotime( $timeframe1->getEndTime() ) && strtotime( $timeframe2->getEndTime() ) &&
				strtotime( $timeframe2->getEndTime() ) > strtotime( $timeframe1->getStartTime() )
			) ||
			(
				strtotime( $timeframe1->getEndTime() ) && strtotime( $timeframe2->getEndTime() ) &&
				(
					( strtotime( $timeframe1->getEndTime() ) > strtotime( $timeframe2->getStartTime() ) && strtotime( $timeframe1->getEndTime() ) < strtotime( $timeframe2->getEndTime() ) ) ||
					( strtotime( $timeframe2->getEndTime() ) > strtotime( $timeframe1->getStartTime() ) && strtotime( $timeframe2->getEndTime() ) < strtotime( $timeframe1->getEndTime() ) )
				)
			);
	}

	/**
	 * Returns true if booking codes shall be shown in frontend.
	 * @return bool
	 */
	public function showBookingCodes() {
		return $this->getMeta( "show-booking-codes" ) == "on";
	}

	/**
	 * Returns repetition-start \DateTime.
	 *
	 * @return \DateTime
	 */
	public function getStartDateDateTime(): \DateTime {
		$startDateString = $this->getMeta( 'repetition-start' );
		$startDate       = new \DateTime();
		$startDate->setTimestamp( $startDateString );

		return $startDate;
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @return \DateTime
	 */
	public function getStartTimeDateTime(): \DateTime {
		$startDateString = $this->getMeta( self::REPETITION_START );
		$startTimeString = $this->getMeta( 'start-time' );
		$startDate       = new \DateTime();
		$startDate->setTimestamp( $startDateString );
		if ( $startTimeString ) {
			$startTime = new \DateTime();
			$startTime->setTimestamp( strtotime( $startTimeString ) );
			$startDate->setTime( $startTime->format( 'H' ), $startTime->format( 'i' ) );
		}

		return $startDate;
	}

	/**
	 * Returns end-date \DateTime.
	 *
	 * @return \DateTime
	 */
	public function getEndDateDateTime(): \DateTime {
		$endDateString = intval( $this->getMeta( self::REPETITION_END ) );
		$endDate       = new \DateTime();
		$endDate->setTimestamp( $endDateString );

		return $endDate;
	}

	/**
	 * Returns endtime-time \DateTime.
	 *
	 * @param null $endDateString
	 *
	 * @return \DateTime
	 */
	public function getEndTimeDateTime( $endDateString = null ): \DateTime {
		$endTimeString = $this->getMeta( 'end-time' );
		$endDate       = new \DateTime();

		if ( $endTimeString ) {
			$endTime = new \DateTime();
			$endTime->setTimestamp( strtotime( $endTimeString ) );
			$endDate->setTime( $endTime->format( 'H' ), $endTime->format( 'i' ) );
		} else {
			$endDate->setTimestamp( $endDateString );
		}

		return $endDate;
	}

	public function isOverBookable(): bool {
		return \CommonsBooking\Wordpress\CustomPostType\Timeframe::isOverBookable( self::getPost() );
	}

	public function isLocked(): bool {
		return \CommonsBooking\Wordpress\CustomPostType\Timeframe::isLocked( self::getPost() );
	}

	/**
	 * @return array|string[]
	 * @throws Exception
	 */
	public function getAdmins(): array {
		$admins           = [];
		$locationAdminIds = $this->getLocation()->getAdmins();
		$itemAdminIds     = $this->getItem()->getAdmins();

		if (
			is_array( $locationAdminIds ) && count( $locationAdminIds ) &&
			is_array( $itemAdminIds ) && count( $itemAdminIds )
		) {
			$admins = array_merge( $locationAdminIds, $itemAdminIds );
		}

		return $admins;
	}

}
