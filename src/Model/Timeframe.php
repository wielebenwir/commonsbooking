<?php

namespace CommonsBooking\Model;

use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Helper\Wordpress;
use DateTime;
use Exception;

/**
 * Class Timeframe
 *
 * @package CommonsBooking\Model
 */
class Timeframe extends CustomPost {
	/**
	 * Error type id.
	 */
	public const ERROR_TYPE = 'timeframeValidationFailed';

	public const REPETITION_START = 'repetition-start';

	public const REPETITION_END = 'repetition-end';

	public const META_LOCATION_ID = 'location-id';

	public const META_ITEM_ID = 'item-id';

	public const META_REPETITION = 'timeframe-repetition';

	public const META_TIMEFRAME_ADVANCE_BOOKING_DAYS = 'timeframe-advance-booking-days';

	public const META_MAX_DAYS = 'timeframe-max-days';

	/**
	 * Return residence in a human readable format
	 *
	 * "From xx.xx.",  "Until xx.xx.", "From xx.xx. until xx.xx.", "no longer available"
	 *
	 * @return string
	 */
	public function formattedBookableDate(): string {
		return self::formatBookableDate( $this->getStartDate() , $this->getEndDate() );
	}

	/**
	 * Return Start (repetition) date timestamp
	 *
	 * @return int
	 */
	public function getStartDate() : int {
		$startDate = $this->getMeta( self::REPETITION_START );

		if ( (string) intval( $startDate ) !== $startDate ) {
			$startDate = strtotime( $startDate );
		}
		else {
			$startDate = intval ($startDate);
		}

		return $startDate;
	}

    /**
     * Return defined end (repetition) date of timeframe
     *
     * @return false|int
     */
    public function getTimeframeEndDate() {
        $endDate = $this->getMeta( self::REPETITION_END );
		if ( (string) intval( $endDate ) != $endDate ) {
			$endDate = strtotime( $endDate );
		} else {
			$endDate = intval( $endDate );
		}

        return $endDate;
    }
	/**
	 * Return End (repetition) date and respects advance booking days setting.
	 *
	 * @return false|int
	 */
	public function getEndDate() {
		$endDate = $this->getTimeframeEndDate();

		// Latest possible booking date
		$latestPossibleBookingDate = $this->getLatestPossibleBookingDateTimestamp();

		// If enddate is < than the latest possible booking date, we use it as end-date
		if ( $endDate < $latestPossibleBookingDate ) {
			return $endDate;
		}

		// if overall enddate of timeframe is > than latest possible booking date,
		// we use latest possible booking date as end date
		return $latestPossibleBookingDate;
	}

	/**
	 * Returns the latest possible booking date as timestamp.
	 *
	 * @return false|int
	 */
	public function getLatestPossibleBookingDateTimestamp() {
		$calculationBase = time();

		// if meta-value not set we define a default value
		$advanceBookingDays = $this->getMeta( TimeFrame::META_TIMEFRAME_ADVANCE_BOOKING_DAYS ) ?:
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS;

		// we subtract one day to reflect the current day in calculation
		$advanceBookingDays --;

		return strtotime( '+ ' . $advanceBookingDays . ' days', $calculationBase );
	}

	/**
	 * @param   int  $startDate
	 * @param   int  $endDate
	 *
	 * @return string
	 */
	public static function formatBookableDate( int $startDate, int $endDate ): string {
		$format = self::getDateFormat();
		$today  = strtotime( 'now' );

		$startDateFormatted = date_i18n( $format, $startDate );
		$endDateFormatted   = date_i18n( $format, $endDate );

		$label           = commonsbooking_sanitizeHTML( __( 'Available here', 'commonsbooking' ) );
		$availableString = '';

		if ( $startDate && $endDate && $startDate === $endDate ) { // available only one day
			/* translators: %s = date in WordPress defined format */
			$availableString = sprintf( commonsbooking_sanitizeHTML( __( 'on %s', 'commonsbooking' ) ), $startDateFormatted );
		} elseif ( $startDate && ! $endDate ) { // start but no end date
			if ( $startDate > $today ) { // start is in the future
				$availableString = sprintf(
					/* translators: %s = date in WordPress defined format */
                    commonsbooking_sanitizeHTML( __( 'from %s', 'commonsbooking' ) ),
                    $startDateFormatted
                );
			} else { // start has passed, no end date, probably a fixed location
				$availableString = commonsbooking_sanitizeHTML( __( 'permanently', 'commonsbooking' ) );
			}
		} elseif ( $startDate && $endDate ) { // start AND end date
			if ( $startDate > $today ) { // start is in the future, with an end date
				$availableString = sprintf(
					/* translators: %1$s = startdate, second %2$s = enddate in WordPress defined format */
                    commonsbooking_sanitizeHTML( __( 'from %1$s until %2$s', 'commonsbooking' ) ),
					$startDateFormatted,
                    $endDateFormatted
                );
			} else { // start has passed, with an end date
				$availableString = sprintf(
					/* translators: %s = enddate in WordPress defined format */
                    commonsbooking_sanitizeHTML( __( 'until %s', 'commonsbooking' ) ),
                    $endDateFormatted
                );
			}
		}

		return $label . ' ' . $availableString;
	}

	/**
	 * Return date format
	 *
	 * @return string
	 */
	public static function getDateFormat(): string {
		return esc_html( get_option( 'date_format' ) );
	}

	/**
	 * Returns end (repetition) date and does not respect advance booking days setting.
     * TODO this or getTimeFrameEndDate can be deprecated
	 * @return false|int
	 */
	public function getRawEndDate() {
		return $this->getTimeframeEndDate();
	}

	/**
	 * Returns true, if the start date is earlier and the end date later than the latest possible booking date.
     *
	 * @return bool
	 */
	public function isBookable() :bool {
		$startDateTimestamp                 = $this->getStartDate();
		$latestPossibleBookingDateTimestamp = $this->getLatestPossibleBookingDateTimestamp();

		return $startDateTimestamp <= $latestPossibleBookingDateTimestamp;
	}

	/**
	 * Return  time format
	 *
	 * @return string
	 */
	public function getTimeFormat(): string {
		return esc_html( get_option( 'time_format' ) );
	}

	/**
	 * Validates if there can be booking codes created for this timeframe.
     *
	 * @return bool
	 */
	public function bookingCodesApplicable(): bool {
		try {
			return $this->getLocation() && $this->getItem() &&
			       $this->getStartDate() && $this->usesBookingCodes() &&
			       $this->getType() === \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @return Location
	 * @throws Exception
	 */
	public function getLocation(): ?Location {
		$locationId = $this->getMeta( self::META_LOCATION_ID );
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
		$itemId = $this->getMeta( self::META_ITEM_ID );
		if ( $itemId ) {
			if ( $post = get_post( $itemId ) ) {
				return new Item( $post );
			}
		}

		return null;
	}

	/**
	 * Returns type id
     *
	 * @return mixed
	 */
	public function getType() : int {
		return intval( $this->getMeta( 'type' ) );
	}

	/**
	* Checks if Timeframe is valid.
	* Will throw a TimeframeInvalidException with error message
	*
	* @return void
	* @throws \CommonsBooking\Exception\TimeframeInvalidException
	*/
	public function isValid(): void {
		if (
			$this->getType() === \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID
		) {
			try {
				$item = $this->getItem();
				$location = $this->getLocation();
			}
			catch ( \Exception $e ) {
				throw new TimeframeInvalidException(__(
						'Could not get item or location. Please set a valid item and location. Timeframe is saved as draft',
						'commonsbooking')
				);
			}
			if ( ! $item || ! $location ) {
				// if location or item is missing
				throw new TimeframeInvalidException(__(
						'Item or location is missing. Please set item and location. Timeframe is saved as draft',
						'commonsbooking'   )
				);
			}

			if ( ! $this->getStartDate() ) {
				// If there is at least one mandatory parameter missing, we cannot save/publish timeframe.
				throw new TimeframeInvalidException( __(
						'Startdate is missing. Timeframe is saved as draft. Please enter a start date to publish this timeframe.',
						'commonsbooking' )
				);
			}

			if (
				$this->getStartDate()
			) {
				$postId = $this->ID;

				if ( $this->getStartTime() && ! $this->getEndTime() && ! $this->isFullDay() ) {
					throw new TimeframeInvalidException( __(
							'A pickup time but no return time has been set. Please set the return time.',
							'commonsbooking' )
					);
				}

				// First we check if the item is already connected to another location to avoid overlapping bookable dates
				$sameItemTimeframes = \CommonsBooking\Repository\Timeframe::getBookable(
					[],
					[ $this->getItem()->ID ],
					null,
					true,
					null,
					[ 'publish' ]
				);

				// check if timeframes of other locations overlap in date and return error message if true
				foreach ( $sameItemTimeframes as $sameItemTimeframe ) {

					if ( $location != $sameItemTimeframe->getLocation()
					     && $this->hasTimeframeDateOverlap( $sameItemTimeframe )
					) {
						throw new TimeframeInvalidException(
						/* translators: %1$s = timeframe-ID, %2$s is timeframe post_title */
							sprintf(
								__(
									'Item is already bookable at another location within the same date range. See other timeframe ID: %1$s: %2$s',
									'commonsbooking'
								),
								'<a href=" ' . get_edit_post_link( $sameItemTimeframe->ID ) . '">' . $sameItemTimeframe->ID . '</a>',
								'<a href=" ' . get_edit_post_link( $sameItemTimeframe->ID ) . '">' . $sameItemTimeframe->post_title . '</a>',
							)
						);
					}
				}

				// Get Timeframes with same location, item and a startdate
				$existingTimeframes = \CommonsBooking\Repository\Timeframe::getBookable(
					[ $this->getLocation()->ID ],
					[ $this->getItem()->ID ],
					null,
					true
				);

				// filter current timeframe
				$existingTimeframes = array_filter(
					$existingTimeframes,
					function ( $timeframe ) use ( $postId ) {
						return $timeframe->ID !== $postId && $timeframe->getStartDate();
					}
				);

				// Validate against existing other timeframes
				foreach ( $existingTimeframes as $timeframe ) {

					// check if timeframes overlap
					if (
						$this->hasTimeframeDateOverlap( $timeframe )
					) {
						// Compare grid types
						if ( $timeframe->getGrid() !== $this->getGrid() ) {
							throw new TimeframeInvalidException(
								sprintf(
									/* translators: %1$s = timeframe-ID, %2$s is timeframe post_title */
									__(
										'Overlapping bookable timeframes are only allowed to have the same grid. See overlapping timeframe ID: %1$s %2$s',
										'commonsbooking',
									),
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->ID . '</a>',
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->post_title . '</a>',
								)
							);
						}

						// Check if different weekdays are set
						if (
							array_key_exists( 'weekdays', $_REQUEST ) &&
							is_array( $_REQUEST['weekdays'] ) &&
							$timeframe->getWeekDays()
						) {
							if ( ! array_intersect( $timeframe->getWeekDays(), $_REQUEST['weekdays'] ) ) {
								return;
							}
						}

						// Check if in day slots overlap
						if ( ! $this->isFullDay() && $this->hasTimeframeTimeOverlap( $timeframe ) ) {
							throw new TimeframeInvalidException(
								sprintf(
									/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
									__(
										'time periods are not allowed to overlap. Please check the other timeframe to avoid overlapping time periods during one specific day. See affected timeframe ID: %1$s %2$s',
										'commonsbooking'
									),
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->ID . '</a>',
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->post_title . '</a>',
								)
							);
						}

						// Check if full-day slots overlap
						if ( $this->isFullDay() ) {
							throw new TimeframeInvalidException(
								sprintf(
									/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
									__(
										'Date periods are not allowed to overlap. Please check the other timeframe to avoid overlapping Date periods. See affected timeframe ID: %1$s %2$s',
										'commonsbooking'
									),
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->ID . '</a>',
									'<a href=" ' . get_edit_post_link( $timeframe->ID ) . '">' . $timeframe->post_title . '</a>'
								)
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Returns start time for day-slots.
     *
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
	 * Returns true if timeframe is full-day
	 * @return bool
	 */
	public function isFullDay() : bool {
		return $this->getMeta( 'full-day' ) === 'on';
	}

	/**
	 * Checks if timeframes are overlapping in date range.
	 *
	 * @param   \CommonsBooking\Model\Timeframe  $otherTimeframe
	 *
	 * @return bool
	 */
	public function hasTimeframeDateOverlap( Timeframe $otherTimeframe ): bool {

        // Check if both timeframes have no end date or if both are ongoing
        if ( ! $this->getTimeframeEndDate() && ! $otherTimeframe->getTimeframeEndDate() ) {
            return true;
        }

        // Check if only one timeframe has an end date
        if ( $this->getTimeframeEndDate() && ! $otherTimeframe->getTimeframeEndDate() ) {
            return ( $otherTimeframe->getStartDate() <= $this->getTimeframeEndDate() && $otherTimeframe->getStartDate() >= $this->getStartDate() );
        }

        if ( ! $this->getTimeframeEndDate() && $otherTimeframe->getTimeframeEndDate() ) {
            return ( $otherTimeframe->getTimeframeEndDate() > $this->getStartDate() );
        }

        // Check if both timeframes have an end date
        if ( $this->getTimeframeEndDate() && $otherTimeframe->getTimeframeEndDate() ) {
            return (
                // Check if the end date of the first timeframe is within the second timeframe
	            ( $this->getTimeframeEndDate() >= $otherTimeframe->getStartDate() && $this->getTimeframeEndDate() <= $otherTimeframe->getTimeframeEndDate() ) ||
	            // Check if the end date of the second timeframe is within the first timeframe
	            ( $otherTimeframe->getTimeframeEndDate() >= $this->getStartDate() && $otherTimeframe->getTimeframeEndDate() <= $this->getTimeframeEndDate() )
            );
        }

		// If none of the above conditions are true, there is no overlap
		return false;
	}

	/**
	 * Returns grit type id
     *
	 * @return mixed
	 */
	public function getGrid() {
		return $this->getMeta( 'grid' );
	}

	/**
	 * Checks if timeframes are overlapping in daily slots.
	 *
	 * @param   \CommonsBooking\Model\Timeframe  $otherTimeframe
	 *
	 * @return bool
	 */

    public function hasTimeframeTimeOverlap( Timeframe $otherTimeframe ) {
        // Check if both timeframes have an end time, if not, there is no overlap
        if ( ! strtotime( $this->getEndTime() ) && ! strtotime( $otherTimeframe->getEndTime() ) ) {
            return true;
        }

        // Check if only timeframe1 has an end time and if it overlaps with the other timeframe
        if ( strtotime( $this->getEndTime() ) && ! strtotime( $otherTimeframe->getEndTime() )
            && strtotime( $otherTimeframe->getStartTime() ) <= strtotime( $this->getEndTime() )
            && strtotime( $otherTimeframe->getStartTime() ) >= strtotime( $this->getStartTime() ) ) {
            return true;
        }

        // Check if only timeframe2 has an end time and if it overlaps with the other timeframe
        if ( ! strtotime( $this->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
            && strtotime( $otherTimeframe->getEndTime() ) > strtotime( $this->getStartTime() ) ) {
            return true;
        }

        // Check if both timeframes have an end time and if they overlap
        if ( strtotime( $this->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
            && ( ( strtotime( $this->getEndTime() ) > strtotime( $otherTimeframe->getStartTime() )
                && strtotime( $this->getEndTime() ) < strtotime( $otherTimeframe->getEndTime() ) )
                || ( strtotime( $otherTimeframe->getEndTime() ) > strtotime( $this->getStartTime() )
                    && strtotime( $otherTimeframe->getEndTime() ) < strtotime( $this->getEndTime() ) ) ) ) {
            return true;
        }

        // If none of the above conditions are true, there is no overlap
        return false;
    }

	/**
	 * Returns weekdays array.
     *
	 * @return mixed
	 */
	public function getWeekDays() {
		return $this->getMeta( 'weekdays' );
	}

	/**
	 * Returns grid size in hours.
     *
	 * @return int|null
	 */
	public function getGridSize(): ?int {
		if ( $this->isFullDay() ) {
			return 24;
		} elseif ( $this->getGrid() === 0 ) {
			$startTime = strtotime( $this->getMeta( 'start-time' ) );
			$endTime   = strtotime( $this->getMeta( 'end-time' ) );

			return intval( round( abs( $endTime - $startTime ) / 3600, 2 ) );
		} else {
			return intval( $this->getGrid() );
		}
	}

	/**
	 * Returns true if booking codes shall be shown in frontend.
     *
	 * @return bool
	 */
	public function showBookingCodes() : bool
    {
		return $this->getMeta( 'show-booking-codes' ) === 'on';
	}

	/**
	 * Returns true if booking codes were enabled for this timeframe
	 *
	 * @return bool
	 */
	public function usesBookingCodes(): bool {
		return $this->getMeta( 'create-booking-codes' ) == 'on';
	}

	/**
	 * Returns repetition-start \DateTime.
	 *
	 * @return DateTime
	 */
	public function getUTCStartDateDateTime(): ?DateTime {
		$startDateString = $this->getMeta( self::REPETITION_START );
		if ( ! $startDateString ) {
			return null;
		}
		if ( $this->isFullDay() ) {
			return Wordpress::getUTCDateTimeByTimestamp( $startDateString );
		}
		return Wordpress::convertTimestampToUTCDatetime( $startDateString );
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @return DateTime
	 */
	public function getStartTimeDateTime(): ?DateTime {
		$startDateString = $this->getMeta( self::REPETITION_START );
		$startTimeString = $this->getMeta( 'start-time' );
		if ( ! $startDateString ) {
			return null;
		}
		$startDate       = Wordpress::getUTCDateTimeByTimestamp( $startDateString );
		if ( $startTimeString ) {
			$startTime = Wordpress::getUTCDateTimeByTimestamp( strtotime( $startTimeString ) );
			$startDate->setTime( $startTime->format( 'H' ), $startTime->format( 'i' ) );
		}

		return $startDate;
	}

	/**
	 * Returns end-date \DateTime.
	 * This method returns a local date time object, just with the UTC timezone attached but the time is still local.
	 *
	 * @return DateTime
	 */
	public function getEndDateDateTime(): ?DateTime {
		$endDateString = intval( $this->getMeta( self::REPETITION_END ) );
		if (! $endDateString ){
			return null;
		}
		return Wordpress::getUTCDateTimeByTimestamp( $endDateString );
	}

	/**
	 * Returns end-date \DateTime.
	 * Provides a UTC date time object.
	 * We need to do this weird conversion because the end date is stored as a local timestamp.
	 *
	 * @return DateTime
	 */
	public function getUTCEndDateDateTime(): ?DateTime {
		$endDateString = intval( $this->getMeta( self::REPETITION_END ) );
		if (! $endDateString ){
			return null;
		}
		if ( $this->isFullDay() ) {
			return Wordpress::getUTCDateTimeByTimestamp( $endDateString );
		}
		return Wordpress::convertTimestampToUTCDatetime( $endDateString );
	}

	/**
	 * Returns endtime-time \DateTime.
	 *
	 * @param null $endDateString
	 *
	 * @return DateTime
	 */
	public function getEndTimeDateTime( $endDateString = null ): DateTime {
		$endTimeString = $this->getEndTime();
		$endDate       = Wordpress::getUTCDateTime();

		if ( $endTimeString ) {
			$endTime = Wordpress::getUTCDateTimeByTimestamp( strtotime( $endTimeString ) );
			$endDate->setTime( $endTime->format( 'H' ), $endTime->format( 'i' ) );
		} else {
			$endDate = Wordpress::getUTCDateTimeByTimestamp( $endDateString );
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
     * Gets the admins for the current timeframe, will combine location and item admins.
	 * @return array|string[] - An array with User IDs
	 * @throws Exception
	 */
	public function getAdmins(): array {
		$admins           = [];
		$location = $this->getLocation();
		if (! empty($location)) {
			$locationAdminIds = $location->getAdmins();
		}
		$item = $this->getItem();
		if (! empty($item)) {
			$itemAdminIds = $item->getAdmins();
		}

		if (
			isset ($locationAdminIds) && isset ($itemAdminIds) &&
			is_array( $locationAdminIds ) && count( $locationAdminIds ) &&
			is_array( $itemAdminIds ) && count( $itemAdminIds )
		) {
			$admins = array_merge( $locationAdminIds, $itemAdminIds );
		}

		return array_unique( $admins );
	}

	/**
	 * Returns type of repetition
     *
	 * @return mixed
	 */
	public function getRepetition() {
		return $this->getMeta( self::META_REPETITION );
	}

    /**
     * Returns first bookable day based on the defined booking startday offset in timeframe
     *
     * @return date string Y-m-d
     */
    public function getFirstBookableDay() {
        $offset = $this->getFieldValue( 'booking-startday-offset' ) ?: 0;
        $today = current_datetime()->format('Y-m-d');
        return date( 'Y-m-d', strtotime( $today . ' + ' . $offset . ' days' ) );

    }
}
