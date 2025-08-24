<?php

namespace CommonsBooking\Service;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\Calendar;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;

/**
 * Functionality that covers the booking calendar.
 */
class CalendarService {

	/**
	 * Returns calendar data
	 *
	 * @param WP_Post|int|string|CustomPost $item
	 * @param WP_Post|int|string|CustomPost $location
	 * @param string                        $startDateString YYYY-MM-DD Format
	 * @param string                        $endDateString YYYY-MM-DD Format
	 * @param bool                          $keepDaterange
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getCalendarDataArray( $item, $location, string $startDateString, string $endDateString, bool $keepDaterange = false ): array {
		if ( $item instanceof WP_Post || $item instanceof CustomPost ) {
			$item = $item->ID;
		}

		if ( $location instanceof WP_Post || $location instanceof CustomPost ) {
			$location = $location->ID;
		}

		if ( ! Wordpress::isValidDateString( $startDateString ) || ! Wordpress::isValidDateString( $endDateString ) ) {
			throw new \Exception( 'invalid date format' );
		}

		if ( ! $item || ! $location ) {
			return [];
		}

		$startDate          = new Day( $startDateString );
		$endDate            = new Day( $endDateString );
		$advanceBookingDays = null;
		$lastBookableDate   = null;
		$firstBookableDay   = null;
		$bookableTimeframes = \CommonsBooking\Repository\Timeframe::getBookableForCurrentUser(
			[ $location ],
			[ $item ],
			null,
			true,
			Helper::getLastFullHourTimestamp()
		);

		if ( count( $bookableTimeframes ) ) {
			$closestBookableTimeframe = self::getClosestBookableTimeFrameForToday( $bookableTimeframes );
			$advanceBookingDays       = intval( $closestBookableTimeframe->getFieldValue( 'timeframe-advance-booking-days' ) );
			$firstBookableDay         = $closestBookableTimeframe->getFirstBookableDay();

			// Only if passed daterange must not be kept
			if ( ! $keepDaterange ) {
				// Check if start-/enddate was requested, then don't change it
				// otherwise start with current day
				$startDateTimestamp = time();
				if ( $closestBookableTimeframe->getStartDate() > $startDateTimestamp ) {
					$startDateTimestamp = $closestBookableTimeframe->getStartDate();
				}

				if ( $startDateTimestamp > $startDate->getDateObject()->getTimestamp() ) {
					$startDate = new Day( date( 'Y-m-d', $startDateTimestamp ) );
				}
			}

			// Last day of month after next as default for calendar view
			// -> we just need to ensure, that pagination is possible
			$endDateTimestamp = self::getDefaultCalendarEnddateTimestamp( $startDate );

			// get max advance booking days based on user defined max days in closest bookable timeframe
			$latestPossibleBookingDateTimestamp = $closestBookableTimeframe->getLatestPossibleBookingDateTimestamp();
			if ( $latestPossibleBookingDateTimestamp < $endDateTimestamp ) {
				$lastBookableDate = $endDateTimestamp = $latestPossibleBookingDateTimestamp;
			}

			// Only if passed daterange must not be kept
			if ( ! $keepDaterange ) {
				$endDateString = date( 'Y-m-d', $endDateTimestamp );
				$endDate       = new Day( $endDateString );
			}
		}

		return self::prepareJsonResponse( $startDate, $endDate, [ $location ], [ $item ], $advanceBookingDays, $lastBookableDate, $firstBookableDay );
	}

	/**
	 * Returns Last day of month after next as default for calendar view,
	 * based on $startDate param.
	 *
	 * @param Day $startDate
	 *
	 * @return int
	 */
	private static function getDefaultCalendarEnddateTimestamp( $startDate ) {
		return strtotime( 'last day of +3 months', $startDate->getDateObject()->getTimestamp() );
	}

	/**
	 * Returns JSON-Data for Litepicker calendar.
	 *
	 * @param Day   $startDate
	 * @param Day   $endDate
	 * @param array $locations []
	 * @param array $items <int|string>
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function prepareJsonResponse(
		Day $startDate,
		Day $endDate,
		array $locations,
		array $items,
		$advanceBookingDays = null,
		$lastBookableDate = null,
		$firstBookableDay = null
	): array {

		$current_user   = wp_get_current_user();
		$customCacheKey = serialize( $current_user->roles );

		// we calculate the max advance booking days here to prepare the notice string in calender json.
		if ( $advanceBookingDays == null ) {
			$advanceBookingDays = date_diff( $startDate->getDateObject(), $endDate->getDateObject() );
			$advanceBookingDays = (int) $advanceBookingDays->format( '%a ' ) + 1;
		}

		if ( $lastBookableDate == null ) {
			$lastBookableDate = strtotime( '+ ' . $advanceBookingDays . ' days midnight' );
		}

		if ( ! ( $jsonResponse = Plugin::getCacheItem( $customCacheKey ) ) ) {
			$calendar = new \CommonsBooking\Model\Calendar(
				$startDate,
				$endDate,
				$locations,
				$items
			);

			$jsonResponse = [
				'minDate'                 => $startDate->getFormattedDate( 'Y-m-d' ),
				'startDate'               => $startDate->getFormattedDate( 'Y-m-d' ),
				'endDate'                 => $endDate->getFormattedDate( 'Y-m-d' ),
				'lang'                    => str_replace( '_', '-', get_locale() ),
				'days'                    => [],
				'bookedDays'              => [],
				'partiallyBookedDays'     => [],
				'lockDays'                => [],
				'holidays'                => [],
				'highlightedDays'         => [],
				'maxDays'                 => null,
				'disallowLockDaysInRange' => true,
				'countLockDaysInRange' => true,
				'advanceBookingDays'      => $advanceBookingDays,
				'mobileCalendarMonthCount' => self::getMobileCalendarMonthCount(),
			];

			if ( count( $locations ) === 1 ) {
				// are overbooking allowed in location options?
				$useGlobalSettings = get_post_meta( $locations[0], COMMONSBOOKING_METABOX_PREFIX . 'use_global_settings', true ) === 'on';
				if ( $useGlobalSettings ) {
					$allowLockedDaysInRange = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range' );
				} else {
					$allowLockedDaysInRange = get_post_meta(
						$locations[0],
						COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range',
						true
					);
				}
				$jsonResponse['disallowLockDaysInRange'] = ! ( $allowLockedDaysInRange === 'on' );

				// should overbooked non bookable days be counted into maxdays selection?
				if ( $useGlobalSettings ) {
					$countLockedDaysInRange = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range' );
				} else {
					$countLockedDaysInRange = get_post_meta(
						$locations[0],
						COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range',
						true
					);
				}
				$jsonResponse['countLockDaysInRange'] = $countLockedDaysInRange === 'on';

				// if yes, what is the maximum amount of days they should count?
				if ( $useGlobalSettings ) {
					$countLockdaysMaximum = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum' );
				} else {
					$countLockdaysMaximum = get_post_meta( $locations[0], COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', true );
				}
				$jsonResponse['countLockDaysMaxDays'] = (int) $countLockdaysMaximum;
			}

			/** @var Week $week */
			foreach ( $calendar->getWeeks() as $week ) {
				/** @var Day $day */
				foreach ( $week->getDays() as $day ) {
					self::mapDay( $day, $lastBookableDate, $endDate, $jsonResponse, $firstBookableDay );
				}
			}

			// set transient expiration time to midnight to force cache refresh by daily basis to allow dynamic advanced booking day feature
			Plugin::setCacheItem( $jsonResponse, [ 'misc' ], $customCacheKey, 'midnight' );
		}

		return $jsonResponse;
	}

	/**
	 * Processes day for calendar view of json.
	 *
	 * @param Day $day
	 * @param $lastBookableDate
	 * @param $endDate
	 * @param $jsonResponse
	 *
	 * @return void
	 */
	protected static function mapDay( $day, $lastBookableDate, $endDate, &$jsonResponse, $firstBookableDay ) {
		$dayArray = [
			'date'               => $day->getFormattedDate( 'd.m.Y' ),
			'slots'              => [],
			'locked'             => false,
			'bookedDay'          => true,
			'partiallyBookedDay' => false,
			'holiday'            => true,
			'repair'             => true,
			'fullDay'            => false,
			'firstSlotBooked'    => null,
			'lastSlotBooked'     => null,
		];

		// If all slots are locked, day cannot be selected
		$allLocked = true;

		// If no slots are existing, day shall be locked
		$noSlots = true;

		// Only process slot if it's in bookingdays in advance range
		if ( $day->getDateObject()->getTimestamp() < $lastBookableDate ) {
			// we process all slots and check status of each slot
			foreach ( $day->getGrid() as $slot ) {
				self::processSlot( $slot, $dayArray, $jsonResponse, $allLocked, $noSlots );
			}

			// If there are no slots defined, there's nothing bookable.
			if ( $noSlots || strtotime( 'today midnight' ) > strtotime( $day->getDate() ) ) {
				$dayArray['locked']    = true;
				$dayArray['holiday']   = false;
				$dayArray['repair']    = false;
				$dayArray['bookedDay'] = false;
			} elseif ( count( $dayArray['slots'] ) === 1 ) {
				$timeframe           = $dayArray['slots'][0]['timeframe'];
				$dayArray['fullDay'] = get_post_meta( $timeframe->ID, 'full-day', true ) == 'on';
			}

			// if day is out max advance booking days range, day is marked as locked to avoid booking
			if ( $day->getDate() > $endDate->getDate() ) {
				$dayArray['locked'] = true;
			}
		} else { // If day is out of booking day in advance range, it's handled like a not bookable day
			$dayArray = self::getLockedDayArray( $day );
		}

		// if day is before minium bookable offset, day is locked
		// We need to add this here and in section predefined day types below too, because
		// renderTable function uses only the days array to generate the calendar.
		if ( $day->getFormattedDate( 'Y-m-d' ) < $firstBookableDay ) {
			$dayArray['locked'] = true;
		}

		// Add day to calendar data.
		$jsonResponse['days'][ $day->getFormattedDate( 'Y-m-d' ) ] = $dayArray;

		// Add day to predefined day types
		if ( $dayArray['locked'] || $allLocked ) {
			if ( $allLocked ) {
				if ( $dayArray['holiday'] ) {
					$jsonResponse['holidays'][] = $day->getFormattedDate( 'Y-m-d' );
					// if all slots are booked or we have a changed timeframe, where a booking was done before change
				} elseif ( $dayArray['bookedDay'] || $dayArray['partiallyBookedDay'] ) {
					$jsonResponse['bookedDays'][] = $day->getFormattedDate( 'Y-m-d' );
				} else {
					$jsonResponse['lockDays'][] = $day->getFormattedDate( 'Y-m-d' );
				}
			} else {
				$jsonResponse['partiallyBookedDays'][] = $day->getFormattedDate( 'Y-m-d' );
			}
		}

		// if day is before minium bookable offset, day is locked
		if ( $day->getFormattedDate( 'Y-m-d' ) < $firstBookableDay ) {
			$jsonResponse['lockDays'][] = $day->getFormattedDate( 'Y-m-d' );
		}
	}

	/**
	 * Extracts calendar relevant data from slot.
	 *
	 * @param $slot
	 * @param $dayArray
	 * @param $jsonResponse
	 * @param $allLocked
	 * @param $noSlots
	 */
	protected static function processSlot( $slot, &$dayArray, &$jsonResponse, &$allLocked, &$noSlots ) {
		// Add only bookable slots for time select
		if ( ! empty( $slot['timeframe'] ) && $slot['timeframe'] instanceof WP_Post ) {
			// We have at least one slot ;)
			$noSlots = false;

			$timeFrameType = get_post_meta( $slot['timeframe']->ID, 'type', true );

			if ( ! $timeFrameType ) {
				$timeFrameType = get_post_meta( $slot['timeframe']->ID, \CommonsBooking\Model\Restriction::META_TYPE, true );
			}

			$isUserAllowedtoBook = commonsbooking_isCurrentUserAllowedToBook( $slot['timeframe']->ID );

			// save bookable state for first and last slot
			if ( $dayArray['firstSlotBooked'] === null ) {
				if ( $timeFrameType == Timeframe::BOOKABLE_ID ) {
					$dayArray['firstSlotBooked'] = false;

					// Set max-days setting based on first found timeframe
					if ( $jsonResponse['maxDays'] == null ) {
						$timeframeMaxDays        = get_post_meta( $slot['timeframe']->ID, \CommonsBooking\Model\Timeframe::META_MAX_DAYS, true );
						$jsonResponse['maxDays'] = intval( $timeframeMaxDays ?: 3 );
					}
				} else {
					$dayArray['firstSlotBooked'] = true;
				}
			}

			// Checks if last slot is booked.
			if ( $timeFrameType == Timeframe::BOOKABLE_ID ) {
				$dayArray['lastSlotBooked'] = false;
			} else {
				$dayArray['lastSlotBooked'] = true;
			}

			// Add slot to array
			$dayArray['slots'][] = $slot;

			// Remove holiday flag, if there is at least one slot that isn't of type holiday
			if ( ! in_array( $timeFrameType, [ Timeframe::HOLIDAYS_ID, Timeframe::OFF_HOLIDAYS_ID ] ) ) {
				$dayArray['holiday'] = false;
			}

			// Remove repair flag, if there is at least one slot that isn't of type repair
			if ( $timeFrameType !== Timeframe::REPAIR_ID ) {
				$dayArray['repair'] = false;
			}

			// Remove bookedDay flag, if there is at least one slot that isn't of type bookedDay
			if ( ! in_array( $timeFrameType, [ Timeframe::BOOKING_ID, Timeframe::REPAIR_ID ] ) ) {
				$dayArray['bookedDay'] = false;
			}

			// Set partiallyBookedDay flag, if there is at least one slot that is not bookable
			if ( in_array(
				$timeFrameType,
				[
					Timeframe::BOOKING_ID,
					Timeframe::HOLIDAYS_ID,
					Timeframe::REPAIR_ID,
					\CommonsBooking\Model\Restriction::TYPE_REPAIR,
				]
			) ) {
				$dayArray['partiallyBookedDay'] = true;
			}

			// If there's a locked timeframe or user ist not allowed to book based on this timeframe, nothing can be selected
			if ( $slot['timeframe']->locked || ! $isUserAllowedtoBook ) {
				$dayArray['locked'] = true;
			} else {
				// if not all slots are locked, the day should be selectable
				$allLocked = false;
			}
		}
	}

	/**
	 * Returns closest timeframe from date/time perspective.
	 *
	 * @param $bookableTimeframes
	 *
	 * @return \CommonsBooking\Model\Timeframe|null
	 */
	public static function getClosestBookableTimeFrameForToday( $bookableTimeframes ): ?\CommonsBooking\Model\Timeframe {
		$today           = new Day( date( 'Y-m-d' ) );
		$todayTimeframes = \CommonsBooking\Repository\Timeframe::filterTimeframesForTimerange( $bookableTimeframes, $today->getStartTimestamp(), $today->getEndTimestamp() );
		$todayTimeframes = array_filter(
			$todayTimeframes,
			function ( $timeframe ) use ( $today ) {
				// also consider repetition
				return $today->isInTimeframe( $timeframe );
			}
		);
		switch ( count( $todayTimeframes ) ) {
			case 1:
				$bookableTimeframes = $todayTimeframes;
				break;
			case 0:
				usort(
					$bookableTimeframes,
					function ( $a, $b ) {
						$aStartDate = $a->getStartDate();
						$bStartDate = $b->getStartDate();

						if ( $aStartDate == $bStartDate ) {
							$aStartTimeDT = $a->getStartTimeDateTime();
							$bStartTimeDT = $b->getStartTimeDateTime();

							return $bStartTimeDT <=> $aStartTimeDT;
						}

						return $bStartDate <=> $aStartDate;
					}
				);
				break;
			default: // More than one timeframe for current day
				// consider starttime and endtime
				$now                = new DateTime();
				$bookableTimeframes = array_filter(
					$todayTimeframes,
					function ( $timeframe ) use ( $now ) {
						$startTime   = $timeframe->getStartTime();
						$startTimeDT = new DateTime( $startTime );
						$endTime     = $timeframe->getEndTime();
						$endTimeDT   = new DateTime( $endTime );

						return $startTimeDT <= $now && $now <= $endTimeDT;
					}
				);

				// condition, that we are not currently in a timeframe
				// for example, we have two timeframes, one from 02:00pm to 04:00pm and one from 04:00pm to 06:00pm.
				// it is currently 11:00am, so we should take the first timeframe
				if ( empty( $bookableTimeframes ) ) {
					usort(
						$todayTimeframes,
						function ( $a, $b ) {
							$aStartTimeDT = $a->getStartTimeDateTime();
							$bStartTimeDT = $b->getStartTimeDateTime();

							return $bStartTimeDT <=> $aStartTimeDT;
						}
					);
					$bookableTimeframes = $todayTimeframes;
				}
				break;
		}

		return array_pop( $bookableTimeframes );
	}
}
