<?php

namespace CommonsBooking\Model;

use CommonsBooking\Exception\OverlappingException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Helper\Wordpress;
use DateTime;
use Exception;

/**
 * This class holds functionality for the timeframe post.
 * It serves as a wrapper for the CustomPostType TimeFrame.
 *
 * You can use the @see \CommonsBooking\Repository\Timeframe class to get the timeframes from the database.
 *
 * @see \CommonsBooking\Wordpress\CustomPostType\Timeframe
 *
 * Additionally, all the public functions in this class can be called using Template Tags.
 *
 * @package CommonsBooking\Model
 *
 * @property bool $locked {@see \CommonsBooking\Wordpress\CustomPostType\Timeframe::isLocked()}
 */
class Timeframe extends CustomPost {
	/**
	 * Error type id.
	 */
	public const ERROR_TYPE = 'timeframeValidationFailed';

	public const ORPHANED_TYPE = 'timeframehasOrphanedBookings';

	public const REPETITION_START = 'repetition-start';

	public const REPETITION_END = 'repetition-end';

	public const META_ITEM_SELECTION_TYPE = 'item-select';

	public const META_ITEM_ID = 'item-id';

	public const META_ITEM_ID_LIST = 'item-id-list';

	public const META_ITEM_CATEGORY_IDS = 'item-category-ids';

	public const META_LOCATION_SELECTION_TYPE = 'location-select';

	public const META_LOCATION_ID = 'location-id';

	public const META_LOCATION_ID_LIST = 'location-id-list';

	public const META_LOCATION_CATEGORY_IDS = 'location-category-ids';

	public const META_REPETITION = 'timeframe-repetition';

	public const META_TIMEFRAME_ADVANCE_BOOKING_DAYS = 'timeframe-advance-booking-days';

	public const META_MAX_DAYS = 'timeframe-max-days';

	public const SELECTION_MANUAL_ID = 0;

	public const SELECTION_CATEGORY_ID = 1;

	public const SELECTION_ALL_ID = 2;

	public const META_CREATE_BOOKING_CODES = 'create-booking-codes';

	public const META_BOOKING_START_DAY_OFFSET = 'booking-startday-offset';

	public const META_SHOW_BOOKING_CODES = 'show-booking-codes';

	public const META_ALLOWED_USER_ROLES = 'allowed_user_roles';

	/**
	 * Dates stored comma separated in the format YYYY-mm-dd.
	 * Example: 2020-01-01,2020-01-02,2020-01-03
	 */
	public const META_MANUAL_SELECTION = 'timeframe_manual_date';
	const MAX_DAYS_DEFAULT             = 3;

	/**
	 * null means the data is not fetched yet
	 *
	 * @var int|null
	 */
	private ?int $repetitionStart = null;
	/**
	 * null means the data is not fetched yet, 0 means there is no end date
	 *
	 * @var int|null
	 */
	private ?int $repetitionEnd = null;

	/**
	 * Return the span of a timeframe in human-readable format
	 *
	 * "From xx.xx.",  "Until xx.xx.", "From xx.xx. until xx.xx.", "no longer available"
	 *
	 * @return string
	 */
	public function formattedBookableDate(): string {
		return self::formatBookableDate( $this->getStartDate(), $this->getEndDate() );
	}

	/**
	 * Return Start (repetition) date timestamp.
	 *
	 * The timestamps are stored in local time (not in UTC).
	 * This means that we do not have to do timezone conversion in order to get the corresponding local time.
	 *
	 * @return int
	 */
	public function getStartDate(): int {
		if ( $this->repetitionStart !== null ) {
			return $this->repetitionStart;
		}

		$startDate = $this->getMeta( self::REPETITION_START );

		if ( ! is_numeric( $startDate ) ) {
			$startDate = strtotime( $startDate );
		} else {
			$startDate = intval( $startDate );
		}

		$this->repetitionStart = $startDate;

		return $startDate;
	}

	/**
	 * Return defined end (repetition) date of timeframe.
	 *
	 * The timestamps are stored in local time (not in UTC).
	 * This means that we do not have to do timezone conversion in order to get the corresponding local time.
	 *
	 * @return false|int Timestamp of end date, false if no end date is set
	 */
	public function getTimeframeEndDate() {
		if ( $this->repetitionEnd !== null ) {
			if ( $this->repetitionEnd === 0 ) {
				return false;
			}
			return $this->repetitionEnd;
		}

		$endDate = $this->getMeta( self::REPETITION_END );

		if ( ! is_numeric( $endDate ) ) {
			$endDate = strtotime( $endDate );
		} else {
			$endDate = intval( $endDate );
		}

		if ( ! $endDate ) {
			$this->repetitionEnd = 0;
		} else {
			$this->repetitionEnd = $endDate;
		}

		return $endDate;
	}

	/**
	 * Return End (repetition) date and respects advance booking days setting.
	 * We need this function in order to display the correct end of the booking period for the user.
	 * Do not use this function to get the actual end date of the timeframe.
	 * Use getRawEndDate() instead.
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

		// if overall enddate of timeframe is > than the latest possible booking date,
		// we use the latest possible booking date as end date
		return $latestPossibleBookingDate;
	}

	/**
	 * Checks if the given user is administrator of item / location or the website and therefore enjoys special booking rights
	 *
	 * @param \WP_User|null $user
	 *
	 * @return bool
	 */
	public function isUserPrivileged( \WP_User $user = null ): bool {
		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		// these roles are always allowed to book
		$privilegedRolesDefaults = [ 'administrator' ];
		/**
		 * Default list of privilege roles
		 *
		 * @since 2.9.0
		 *
		 * @param string[] $privilegedRolesDefaults list of roles as strings that are privileged roles
		 */
		$privilegedRoles = apply_filters( 'commonsbooking_privileged_roles', $privilegedRolesDefaults );
		if ( ! empty( array_intersect( $privilegedRoles, $user->roles ) ) ) {
			return true;
		}

		$itemAdmin     = commonsbooking_isUserAllowedToEdit( $this->getItem(), $user );
		$locationAdmin = commonsbooking_isUserAllowedToEdit( $this->getLocation(), $user );
		return ( $itemAdmin || $locationAdmin );
	}

	/**
	 * Returns the latest possible booking date as timestamp.
	 * This function respects the advance booking days setting.
	 * This means that this is the latest date that a user can currently book.
	 *
	 * TODO / CAREFUL: This does not respect the end of the timeframe. So if the timeframe ends before
	 *       the configured "advance booking days" setting, the function will return a date later than the end date of the timeframe.
	 *
	 * @return false|int
	 */
	public function getLatestPossibleBookingDateTimestamp() {
		$calculationBase = time();

		// if meta-value not set we define a default value far in the future so that we count all possibly relevant timeframes
		$advanceBookingDays = $this->getMeta( self::META_TIMEFRAME_ADVANCE_BOOKING_DAYS ) ?: 365;

		// we subtract one day to reflect the current day in calculation
		--$advanceBookingDays;

		$advanceBookingTime = strtotime( '+ ' . $advanceBookingDays . ' days', $calculationBase );

		return $advanceBookingTime;
	}

	/**
	 * This function will get the formatted end date of the timeframe.
	 * This is used to display the end date of the timeframe in the frontend.
	 * This is mainly in use by the [cb_items] shortcode.
	 *
	 * @param   int $startDate
	 * @param   int $endDate
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
	 * Return date format from WordPress settings.
	 *
	 * @return string
	 */
	public static function getDateFormat(): string {
		return esc_html( get_option( 'date_format' ) );
	}

	/**
	 * Returns end (repetition) date and does not respect advance booking days setting.
	 * Use the getEndDate() function if you want to get the end date that respects the advance booking days setting.
	 *
	 * TODO this or getTimeFrameEndDate can be deprecated
	 *
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
	public function isBookable(): bool {
		$startDateTimestamp                 = $this->getStartDate();
		$latestPossibleBookingDateTimestamp = $this->getLatestPossibleBookingDateTimestamp();

		return $startDateTimestamp <= $latestPossibleBookingDateTimestamp;
	}

	/**
	 * Return time format
	 *
	 * @return string
	 */
	public function getTimeFormat(): string {
		return esc_html( get_option( 'time_format' ) );
	}

	/**
	 * Validates if there can be booking codes created for this timeframe.
	 *
	 * TODO: #507
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
	 * Will get the corresponding location for this timeframe.
	 * This function will return null if no location is set.
	 * This should not happen, because the location is a required field.
	 * But it might happen if the location was deleted.
	 *
	 * @deprecated 2.9.0 This should not be used for Timeframes of type HOLIDAYS_ID.
	 * Use the getLocations() method instead.
	 * @return Location
	 * @throws Exception
	 */
	public function getLocation(): ?Location {
		$locationId = $this->getLocationID();
		if ( $locationId ) {
			if ( $post = get_post( $locationId ) ) {
				return new Location( $post );
			}
		}

		return null;
	}

	/**
	 * Returns the corresponding single location id for a timeframe.
	 * This will solely rely on the location id stored in the timeframe.
	 * If the location is deleted, this function will still return the old location id.
	 *
	 * @deprecated 2.9.0 This should not be used for Timeframes of type HOLIDAYS_ID.
	 *
	 * @return int|null
	 */
	public function getLocationID(): ?int {
		$locationId = $this->getMeta( self::META_LOCATION_ID );
		if ( $locationId ) {
			return intval( $locationId );
		}

		return null;
	}

	/**
	 * Returns the corresponding multiple locations for a timeframe.
	 * If multiple locations are not available, it will call the getLocation() method and return an array with one location.
	 *
	 * @since 2.9 (anticipated)
	 * @return Location[]
	 */
	public function getLocations(): ?array {
		$locationIds = $this->getLocationIDs();
		if ( $locationIds ) {
			$locations = [];
			foreach ( $locationIds as $locationId ) {
				if ( $post = get_post( $locationId ) ) {
					$locations[] = new Location( $post );
				}
			}

			return $locations;
		} else {
			return [];
		}
	}

	/**
	 * Returns the corresponding location ids for a timeframe.
	 * If multiple locations are not available, it will call the getLocationID() method and return an array with one location id.
	 *
	 * @since 2.9 (anticipated)
	 * @return int[]
	 */
	public function getLocationIDs(): array {
		$locationIds = $this->getMeta( self::META_LOCATION_ID_LIST );
		if ( $locationIds ) {
			return array_map( 'intval', $locationIds );
		} else {
			$locationId = $this->getLocationID();
			if ( $locationId ) {
				return [ $locationId ];
			} else {
				return [];
			}
		}
	}

	/**
	 * Get the corresponding single item for a timeframe.
	 * Will get corresponding item object for this timeframe.
	 * This function will return null if no item is set.
	 * This should not happen, because the item is a required field.
	 * But it might happen if the item was deleted.
	 *
	 * @deprecated 2.9.0 This method should not be used for timeframes of the type HOLIDAYS_ID.
	 * @return Item
	 * @throws Exception
	 */
	public function getItem(): ?Item {
		$itemId = $this->getItemID();
		if ( $itemId ) {
			if ( $post = get_post( $itemId ) ) {
				return new Item( $post );
			}
		}

		return null;
	}

	/**
	 * Returns the corresponding single item id for a timeframe.
	 * This will solely rely on the item id stored in the timeframe. If the item is deleted, this function will still return the old item id.
	 *
	 * @deprecated 2.9.0 This method does not work for timeframes of the type HOLIDAYS_ID.
	 * @return int|null
	 */
	public function getItemID(): ?int {
		$itemId = $this->getMeta( self::META_ITEM_ID );
		if ( $itemId ) {
			return intval( $itemId );
		}

		return null;
	}

	/**
	 * Gets the corresponding multiple items for a timeframe.
	 * If multiple items are not available, it will call the getItem() method and return an array with one item.
	 *
	 * @since 2.9 (anticipated)
	 * @return Item[]
	 */
	public function getItems(): ?array {
		$itemIds = $this->getItemIDs();
		if ( $itemIds ) {
			$items = [];
			foreach ( $itemIds as $itemId ) {
				if ( $post = get_post( $itemId ) ) {
					$items[] = new Item( $post );
				}
			}

			return $items;
		} else {
			return [];
		}
	}

	/**
	 * Returns the corresponding item ids for a timeframe.
	 * If multiple items are not available, it will call the getItemID() method and return an array with one item id.
	 *
	 * @since 2.9 (anticipated)
	 * @return int[] - array of item ids, empty array if no item ids are set
	 */
	public function getItemIDs(): array {
		$itemIds = $this->getMeta( self::META_ITEM_ID_LIST );
		if ( $itemIds ) {
			return array_map( 'intval', $itemIds );
		} else {
			$itemId = $this->getItemID();
			if ( $itemId ) {
				return [ $itemId ];
			} else {
				return [];
			}
		}
	}

	/**
	 * Returns type id
	 * The type of the timeframe are constants defined in @see \CommonsBooking\Wordpress\CustomPostType\Timeframe
	 *
	 * The types are usually:
	 * Timeframe::BOOKABLE_ID
	 * Timeframe::HOLIDAYS_ID
	 * Timeframe::REPAIR_ID
	 * Timeframe::BOOKING_ID
	 * Timeframe::BOOKING_CANCELLED_ID
	 *
	 * @return int
	 */
	public function getType(): int {
		return intval( $this->getMeta( 'type' ) );
	}

	/**
	 * Checks if Timeframe is valid. This should be called before publishing a timeframe as it will prevent broken timeframes / configurations.
	 *
	 * First checks missing values, then validates against existing timeframes.
	 * Will check for the mandatory item / location fields.
	 * Will check if the start- and end-date are set.
	 * Will check if there is no timeframe with the same item and location that overlaps with this timeframe.
	 *
	 * Will throw a TimeframeInvalidException with error message
	 *
	 * @return true if valid
	 * @throws \CommonsBooking\Exception\TimeframeInvalidException
	 */
	public function isValid(): bool {
		if (
			$this->getType() === \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID
		) {
			try {
				$item     = $this->getItem();
				$location = $this->getLocation();
			} catch ( \Exception $e ) {
				throw new TimeframeInvalidException(
					__(
						'Could not get item or location. Please set a valid item and location.',
						'commonsbooking'
					)
				);
			}
			if ( ! $item || ! $location ) {
				// if location or item is missing
				throw new TimeframeInvalidException(
					__(
						'Item or location is missing. Please set item and location.',
						'commonsbooking'
					)
				);
			}

			// a timeframe with a manual repetition does not need a start date.
			// start- and enddate are automatically set upon saving the post
			if ( $this->getRepetition() == 'manual' ) {
				$manual_selection_dates = $this->getManualSelectionDates();
				if ( empty( $manual_selection_dates ) ) {
					throw new TimeframeInvalidException(
						__(
							'No dates selected. Please select at least one date.',
							'commonsbooking'
						)
					);
				}
				// make sure that there are no duplicate dates
				$unique_dates = array_unique( $manual_selection_dates );
				if ( count( $unique_dates ) != count( $manual_selection_dates ) ) {
					throw new TimeframeInvalidException(
						__(
							'The same date was selected multiple times. Please select each date only once.',
							'commonsbooking'
						)
					);
				}
			} elseif ( ! $this->getStartDate() ) {
					// If there is at least one mandatory parameter missing, we cannot save/publish timeframe.
					throw new TimeframeInvalidException(
						__(
							'Startdate is missing. Please enter a start date to publish this timeframe.',
							'commonsbooking'
						)
					);
			}

			if (
				$this->getStartDate()
			) {
				$postId = $this->ID;

				if ( $this->getStartTime() && ! $this->getEndTime() && ! $this->isFullDay() ) {
					throw new TimeframeInvalidException(
						__(
							'A pickup time but no return time has been set. Please set the return time.',
							'commonsbooking'
						)
					);
				}

				// check if end date is before start date
				if ( $this->getEndDate() && ( $this->getStartDate() > $this->getTimeframeEndDate() ) ) {
					throw new TimeframeInvalidException(
						__(
							'End date is before start date. Please set a valid end date.',
							'commonsbooking'
						)
					);
				}

				// check if start-time and end-time are the same
				if ( ( $this->getStartTime() && $this->getEndTime() ) && ( $this->getStartTime() == $this->getEndTime() ) ) {
					throw new TimeframeInvalidException(
						__(
							'The start- and end-time of the timeframe can not be the same. Please check the full-day checkbox if you want users to be able to book the full day.',
							'commonsbooking'
						)
					);
				}

				// First we check if the item is already connected to another location to avoid overlapping bookable dates
				$sameItemTimeframes = \CommonsBooking\Repository\Timeframe::getBookable(
					[],
					[ $this->getItemID() ],
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
					[ $this->getLocationID() ],
					[ $this->getItemID() ],
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
				foreach ( $existingTimeframes as $otherTimeframe ) {
					try {
						$this->overlaps( $otherTimeframe );
					} catch ( OverlappingException $e ) {
						throw new TimeframeInvalidException(
							$e->getMessage() .
							sprintf(
							/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
								__( 'See overlapping timeframe ID: %1$s %2$s', 'commonsbooking' ),
								'<a href=" ' . get_edit_post_link( $otherTimeframe->ID ) . '">' . $otherTimeframe->ID . '</a>',
								'<a href=" ' . get_edit_post_link( $otherTimeframe->ID ) . '">' . $otherTimeframe->post_title . '</a>'
							)
						);
					}
				}
			}
		}
		return true;
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
	 *
	 * @return mixed
	 */
	public function getEndTime() {
		return $this->getMeta( 'end-time' );
	}

	/**
	 * Returns true if timeframe is spanning over the whole day.
	 * This means that this is not an hourly timeframe or a slot timeframe.
	 *
	 * @return bool
	 */
	public function isFullDay(): bool {
		return $this->getMeta( 'full-day' ) === 'on';
	}

	/**
	 * Checks if timeframes are overlapping in date range.
	 *
	 * @param Timeframe $otherTimeframe
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
		// TODO: When does this condition ever apply?
		return false;
	}

	/**
	 * Will return false if the timeframes do not overlap in date range or time range.
	 * Will throw an exception with the formatted error message and the affected timeframe if the timeframes overlap.
	 *
	 * TODO: Refactor to return true if timeframes overlap and false if not. Throw exception in calling function.
	 *
	 * @uses Timeframe::hasTimeframeDateOverlap()
	 * @uses Timeframe::hasTimeframeTimeOverlap()
	 *
	 * @param Timeframe $otherTimeframe
	 *
	 * @return false
	 * @throws OverlappingException
	 */
	public function overlaps( Timeframe $otherTimeframe ): bool {
		if (
			$this->hasTimeframeDateOverlap( $otherTimeframe )
		) {
			// Compare grid types
			if ( $otherTimeframe->getGrid() !== $this->getGrid() ) {
				throw new OverlappingException(
					__( 'Overlapping bookable timeframes are only allowed to have the same grid.', 'commonsbooking' )
				);
			}

			// timeframes that don't overlap in time range are not overlapping
			if ( ! $this->hasTimeframeTimeOverlap( $otherTimeframe ) ) {
				return false;
			}

			$otherTimeframeRepetition = $otherTimeframe->getRepetition();
			$repetition               = $this->getRepetition();

			// One of the timeframes takes up the full day and therefore none of the dates can overlap
			// at this stage there is already overlap in the date range and time range, therefore we must check if the repetitions create an overlap
			if ( $repetition === 'd' || $otherTimeframeRepetition === 'd' ) {
				throw new OverlappingException(
					__( 'Daily repeated time periods are not allowed to overlap.', 'commonsbooking' )
				);
			}

			// we concatenate the repetitions to make the switch statement more readable
			switch ( $repetition . '|' . $otherTimeframeRepetition ) {
				case 'w|w':
					if ( $this->getWeekDays() && $otherTimeframe->getWeekDays() ) {
						$weekDaysOverlap = array_intersect(
							$this->getWeekDays(),
							$otherTimeframe->getWeekDays()
						);
						if ( ! empty( $weekDaysOverlap ) ) {
							throw new OverlappingException(
								__( 'Overlapping bookable timeframes are not allowed to have the same weekdays.', 'commonsbooking' )
							);
						}
					}
					break;
				case 'manual|manual':
					$manualDateOverlap = array_intersect(
						$this->getManualSelectionDates(),
						$otherTimeframe->getManualSelectionDates()
					);
					if ( ! empty( $manualDateOverlap ) ) {
						throw new OverlappingException(
							__( 'Overlapping bookable timeframes are not allowed to have the same dates.', 'commonsbooking' )
						);
					}
					break;
				case 'w|manual':
					if ( self::hasWeeklyManualOverlap( $this, $otherTimeframe ) ) {
						throw new OverlappingException(
							__( 'The other timeframe is overlapping with your weekly configuration.', 'commonsbooking' )
						);
					}
					break;
				case 'manual|w':
					if ( self::hasWeeklyManualOverlap( $otherTimeframe, $this ) ) {
						throw new OverlappingException(
							__( 'The other timeframe is overlapping with your weekly configuration.', 'commonsbooking' )
						);
					}
					break;
			}
		}
		return false;
	}

	/**
	 * Checks if timeframes are overlapping in weekly slot and slot with manual repetition.
	 *
	 * @param $weeklyTimeframe
	 * @param $manualTimeframe
	 *
	 * @return bool
	 */
	private static function hasWeeklyManualOverlap( $weeklyTimeframe, $manualTimeframe ): bool {
		$manualSelectionWeekdays = array_unique(
			array_map(
				fn ( $date ) => date( 'w', strtotime( $date ) ),
				$manualTimeframe->getManualSelectionDates()
			)
		);
		// we have to make the sunday a 7 instead of 0 in order to detect overlaps with our other array correctly
		$manualSelectionWeekdays = array_map(
			fn ( $weekday ) => $weekday == 0 ? 7 : $weekday,
			$manualSelectionWeekdays
		);
		$weekDaysOverlap         = array_intersect( $weeklyTimeframe->getWeekDays(), $manualSelectionWeekdays );
		if ( ! empty( $weekDaysOverlap ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Will add the timeframe start and end date to the post meta when the repetition is set to manual.
	 * We have to do this so the user doesn't have to set the start and end date manually when selecting dates.
	 *
	 * @return void
	 */
	public function updatePostMetaStartAndEndDate(): void {
		if ( $this->getRepetition() == 'manual' ) {
			$timestamps = array_map( 'strtotime', $this->getManualSelectionDates() );
			asort( $timestamps );
			update_post_meta( $this->ID, self::REPETITION_START, reset( $timestamps ) );
			update_post_meta( $this->ID, self::REPETITION_END, end( $timestamps ) );
		}
	}


	/**
	 * Returns grid type id.
	 * The timeframe grid describes if either the full slot is bookable or if the timeframe is bookable hourly.
	 * 0 = slot
	 * 1 = hourly
	 *
	 * @return int
	 */
	public function getGrid(): int {
		return intval( $this->getMeta( 'grid' ) );
	}

	/**
	 * Checks if timeframes are overlapping in time ranges or daily slots.
	 *
	 * Use {@see Timeframe::overlaps()} if you want to compute full-overlap between two timeframes.
	 *
	 * @param Timeframe $otherTimeframe
	 *
	 * @return bool If start-time and end-time overlaps, regardless of overlapping start-date and end-date.
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

		// Check if both timeframes have the same start and end time
		if ( strtotime( $this->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $this->getEndTime() ) === strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $this->getStartTime() ) === strtotime( $otherTimeframe->getStartTime() ) ) {
			return true;
		}

		// If none of the above conditions are true, there is no overlap
		return false;
	}

	/**
	 * Returns weekdays array.
	 * This means what weekdays are selected for this timeframe.
	 * This only makes sense when the grid is repeating weekly.
	 *
	 * @return mixed
	 */
	public function getWeekDays() {
		return $this->getMeta( 'weekdays' );
	}

	/**
	 * Returns grid size in hours.
	 * This means the length of the individual bookable slots.
	 * For example if the grid is 2, the bookable slots are 2 hours long.
	 *
	 * @return int|null
	 */
	public function getGridSize(): ?int {
		if ( $this->isFullDay() ) {
			return 24;
		} elseif ( $this->getGrid() === 0 ) {
			// this is for slot timeframes
			$startTime = strtotime( $this->getMeta( 'start-time' ) );
			$endTime   = strtotime( $this->getMeta( 'end-time' ) );

			return intval( round( abs( $endTime - $startTime ) / 3600, 2 ) );
		} else {
			// this is for hourly timeframes, the grid will be 1, because each hour is bookable
			return intval( $this->getGrid() );
		}
	}

	/**
	 * Gets an array of dates that were manually selected by the user.
	 * The dates are in the format YYYY-MM-DD
	 *
	 * @return String[]
	 */
	public function getManualSelectionDates(): array {
		$manualDatesString = $this->getMeta( self::META_MANUAL_SELECTION );
		if ( ! $manualDatesString ) {
			return [];
		}
		return array_map(
			'trim',
			explode( ',', $manualDatesString )
		);
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
	 * Returns true if booking codes were enabled for this timeframe
	 *
	 * @return bool
	 */
	public function usesBookingCodes(): bool {
		return $this->getMeta( self::META_CREATE_BOOKING_CODES ) == 'on';
	}

	/**
	 * Returns true if booking codes were enabled for this timeframe
	 *
	 * @return bool
	 */
	public function hasBookingCodes(): bool {
		return $this->getMeta( 'create-booking-codes' ) == 'on';
	}

	/**
	 * Returns repetition-start \DateTime.
	 * This function contains a weird hotfix for full day timeframes.
	 * This is because it is mainly used by the iCalendar export where if we don't convert the timestamp to a UTC Datetime we will get the wrong starting time.
	 * This, however is not the case for full day timeframes where we need to use the given timestamp, else the date would span over two days when it is only supposed to span over one.
	 *
	 * TODO: Can throw uncaught exception.
	 * TODO: Find better solution for weird hotfix.
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public function getStartDateDateTime(): DateTime {
		$startDateString = $this->getMeta( self::REPETITION_START );
		return Wordpress::getUTCDateTimeByTimestamp( $startDateString );
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
	 * This method returns a local date time object, just with the UTC timezone attached but the time is still local.
	 *
	 * TODO: Can throw uncaught exception.
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public function getStartTimeDateTime(): ?DateTime {
		$startDateString = $this->getMeta( self::REPETITION_START );
		$startTimeString = $this->getMeta( 'start-time' );
		if ( ! $startDateString ) {
			return null;
		}
		$startDate = Wordpress::getUTCDateTimeByTimestamp( $startDateString );
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
	 * TODO: Can throw uncaught exception.
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public function getEndDateDateTime(): ?DateTime {
		$endDateString = intval( $this->getMeta( self::REPETITION_END ) );
		if ( ! $endDateString ) {
			return null;
		}
		return Wordpress::getUTCDateTimeByTimestamp( $endDateString );
	}

	/**
	 * Returns end-date \DateTime.
	 * Provides a UTC date time object.
	 * We need to do this weird conversion because the end date is stored as a local timestamp.
	 *
	 * TODO: Can throw uncaught exception.
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public function getUTCEndDateDateTime(): ?DateTime {
		$endDateString = intval( $this->getMeta( self::REPETITION_END ) );
		if ( ! $endDateString ) {
			return null;
		}
		if ( $this->isFullDay() ) {
			return Wordpress::getUTCDateTimeByTimestamp( $endDateString );
		}
		return Wordpress::convertTimestampToUTCDatetime( $endDateString );
	}

	/**
	 * Returns endtime-time \DateTime.
	 * This method returns a local date time object, just with the UTC timezone attached but the time is still local.
	 *
	 * TODO: Clarify what the exact difference between endTime and endDate is.
	 *
	 * @param null $endDateString
	 *
	 * @return DateTime
	 * @throws Exception
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

	/**
	 * Delegation only - see uses.
	 *
	 * @uses \CommonsBooking\Wordpress\CustomPostType\Timeframe::isOverBookable()
	 *
	 * @return bool
	 */
	public function isOverBookable(): bool {
		return \CommonsBooking\Wordpress\CustomPostType\Timeframe::isOverBookable( self::getPost() );
	}

	/**
	 * Delegation only - see uses.
	 *
	 * @uses \CommonsBooking\Wordpress\CustomPostType\Timeframe::isLocked()
	 *
	 * @return bool
	 */
	public function isLocked(): bool {
		return \CommonsBooking\Wordpress\CustomPostType\Timeframe::isLocked( self::getPost() );
	}

	/**
	 * Returns users with admin role for location and item, assigned to this timeframe.
	 * Will call the respective methods on the location and item.
	 *
	 * @return array|string[]
	 * @throws Exception
	 */
	public function getAdmins(): array {

		$itemAdminIds     = [];
		$locationAdminIds = [];

		$location = $this->getLocation();
		if ( ! empty( $location ) ) {
			$locationAdminIds = $location->getAdmins();
		}
		$item = $this->getItem();
		if ( ! empty( $item ) ) {
			$itemAdminIds = $item->getAdmins();
		}

		if ( empty( $locationAdminIds ) && empty( $itemAdminIds ) ) {
			return [];
		}
		if ( empty( $locationAdminIds ) ) {
			return $itemAdminIds;
		}
		if ( empty( $itemAdminIds ) ) {
			return $locationAdminIds;
		}

		return array_unique( array_merge( $locationAdminIds, $itemAdminIds ) );
	}

	/**
	 * Returns type of repetition.
	 * Possible values:
	 * d = daily
	 * w = weekly
	 * m = monthly
	 * y = yearly
	 * manual = manual selection of dates
	 * norep = no repetition
	 *
	 * @return mixed
	 */
	public function getRepetition() {
		return $this->getMeta( self::META_REPETITION );
	}

	/**
	 * Returns first bookable day based on the defined booking startday offset in timeframe
	 *
	 * @return string  date format Y-m-d
	 */
	public function getFirstBookableDay() {
		$offset = $this->getFieldValue( self::META_BOOKING_START_DAY_OFFSET ) ?: 0;
		$today  = current_datetime()->format( 'Y-m-d' );
		return date( 'Y-m-d', strtotime( $today . ' + ' . $offset . ' days' ) );
	}

	/**
	 * @return int
	 */
	public function getMaxDays(): int {
		$meta = $this->getMeta( self::META_MAX_DAYS );
		if ( is_numeric( $meta ) ) {
			return (int) $meta;
		}
		return self::MAX_DAYS_DEFAULT;
	}
}
