<?php


namespace CommonsBooking\View;


use CommonsBooking\CB\CB;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use DateTime;
use Exception;
use WP_Post;

class Calendar {

	/**
	 * Default range until calendar end date.
	 */
	public const DEFAULT_RANGE = '+3 months';

	/**
	 * Default start of calendar data.
	 */
	public const DEFAULT_RANGE_START = 'first day of this month';

	/**
	 * Renders item table.
	 * Many thanks to fLotte Berlin!
	 * Forked from https://github.com/flotte-berlin/cb-shortcodes/blob/master/custom-shortcodes-cb-items.php
	 *
	 * @param $atts
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function renderTable( $atts ): string {
		$locationCategory = false;
		if ( is_array( $atts ) && array_key_exists( 'locationcat', $atts ) ) {
			$locationCategory = $atts['locationcat'];
		}
		$itemCategory = false;
		if ( is_array( $atts ) && array_key_exists( 'itemcat', $atts ) ) {
			$itemCategory = $atts['itemcat'];
		}

		// defines the number of days shown in the calendar table view. If not set, default is 31 days
		// TODO: max days should be made configurable in options
		$days = is_array( $atts ) && array_key_exists( 'days', $atts ) ? $atts['days'] : 31;

		$desc  = $atts['desc'] ?? '';
		$date  = new DateTime();
		$today = $date->format( "Y-m-d" );

		$days_display = array_fill( 0, $days, 'n' );
		$days_cols    = array_fill( 0, $days, '<col>' );
		$month        = date( "m" );
		$month_cols   = 0;
		$colspan      = $days;

		for ( $i = 0; $i < $days; $i ++ ) {
			$month_cols ++;
			$days_display[ $i ] = $date->format( 'd' );
			$days_dates[ $i ]   = $date->format( 'Y-m-d' );
			$days_weekday[ $i ] = $date->format( 'N' );
			$daysDM[ $i ]       = $date->format( 'j.n.' );
			if ( $date->format( 'N' ) >= 7 ) {
				$days_cols[ $i ] = '<col class="bg_we">';
			}
			$date->modify( '+1 day' );
			if ( $date->format( 'm' ) != $month ) {
				$colspan    = $month_cols;
				$month_cols = 0;
				$month      = $date->format( 'm' );
			}
		}

		$last_day = $days_dates[ $days - 1 ];
		$colStr   = implode( ' ', $days_cols );

		$print = '<div class="cb-table-scroll">';
		$print .= "<table class='cb-items-table tablesorter'><colgroup><col><col>" . $colStr . "</colgroup><thead>";
		$print .= "<tr><th colspan='2' class='sortless'>" . $desc . "</th><th class='sortless' colspan='" . $colspan . "'>";

		if ( $colspan > 1 ) {
			$print .= date_i18n( 'F' ) . "</th>";
		} else {
			$print .= date_i18n( 'M' ) . "</th>";
		}

		// Render months
		if ( $colspan < $days ) {
			$print .= self::renderHeadlineMonths($month_cols, $days_dates, $days);
		}

		//Render Headline Days
		$print .= self::renderHeadlineDays($days_display);

		$items = get_posts( array(
			'post_type'      => 'cb_item',
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'posts_per_page' => - 1
		) );

		foreach ( $items as $item ) {
			// Check for category term
			if ( $itemCategory ) {
				if ( ! has_term( $itemCategory, Item::$postType . 's_category', $item->ID ) ) {
					continue;
				}
			}

			// Get timeframes for item
			$timeframes = \CommonsBooking\Repository\Timeframe::getInRange(
				strtotime( $today ),
				strtotime( $last_day ),
				[],
				[ $item->ID ],
				[ Timeframe::BOOKABLE_ID ],
				true
			);

			if ( $timeframes ) {

				// Collect unique locations from timeframes
				$locations = [];
				foreach ( $timeframes as $timeframe ) {
					$locations[ $timeframe->getLocation()->ID ] = $timeframe->getLocation()->post_title;
				}

				// loop through location
				foreach ( $locations as $locationId => $locationName ) {

					// Check for category term
					if ( $locationCategory ) {
						if ( ! has_term( $locationCategory, Location::$postType . 's_category', $locationId ) ) {
							continue;
						}
					}

					$print .= self::renderItemLocationRow($item, $locationId, $locationName, $today, $last_day, $days, $days_display);
				}
			}
		}

		$print .= "</tbody></table>";
		$print .= '</div>';

		return $print;
	}

	/**
	 * Renders months in headline.
	 * @param $month_cols
	 * @param $days_dates
	 * @param $days
	 *
	 * @return string
	 */
	protected static function renderHeadlineMonths($month_cols, $days_dates, $days): string {
		$month2 = date_i18n( 'M', strtotime( $days_dates[ $days - 1 ] ) );
		if ( $month_cols > 1 ) {
			$month2 = date_i18n( 'F', strtotime( $days_dates[ $days - 1 ] ) );
		}

		return "<th class='sortless' colspan='" . $month_cols . "'>" . $month2 . "</th>";
	}

	/**
	 * Renders days in headline.
	 * @param $days_display
	 *
	 * @return string
	 */
	protected static function renderHeadlineDays($days_display): string {
		$divider  = "</th><th class='cal sortless'>";
		$dayStr   = implode( $divider, $days_display );

		return "</tr><tr>" .
		            "<th>" . __( "Item", "commonsbooking" ) . "</th>" .
		            "<th>" . __( "Location", "commonsbooking" ) . "<th class='cal sortless'>" . $dayStr . "</th>" .
		            "</tr></thead><tbody>";
	}

	/**
	 * Renders item/location row.
	 * @param $item
	 * @param $locationId
	 * @param $locationName
	 * @param $today
	 * @param $last_day
	 * @param $days
	 * @param $days_display
	 *
	 * @return string
	 * @throws Exception
	 */
	protected static function renderItemLocationRow($item, $locationId, $locationName, $today, $last_day, $days, $days_display): string {
		$divider = "</td><td>";
		$itemName = $item->post_title;

		// Get data for current item/location combination
		$calendarData = self::getCalendarDataArray(
			$item->ID,
			$locationId,
			$today,
			date( 'Y-m-d', strtotime( '+' . $days . ' days', time() ) )
		);

		$gotStartDate = false;
		$gotEndDate   = false;
		$dayIterator  = 0;
		foreach ( $calendarData['days'] as $day => $data ) {

			// skip days until we are at today
			if ( ! $gotStartDate ) {
				if ( $today <= $day ) {
					$gotStartDate = true;
				} else {
					continue;
				}
			}

			if ( $gotEndDate ) {
				continue;
			}

			if ( $day == $last_day ) {
				$gotEndDate = true;
			}

			// Check day state
			if ( ! count( $data['slots'] ) ) {
				$days_display[ $dayIterator ++ ] = "<span class='unavailable'></span>";
			} elseif ( $data['holiday'] ) {
				$days_display[ $dayIterator ++ ] = "<span class='holiday'></span>";
			} elseif ( $data['locked'] ) {
				if ( $data['firstSlotBooked'] && $data['lastSlotBooked'] ) {
					$days_display[ $dayIterator ++ ] = "<span class='blocked'></span>";
				} elseif ( $data['partiallyBookedDay'] ) {
					$days_display[ $dayIterator ++ ] = "<span class='booked'></span>";
				}
			} else {
				$days_display[ $dayIterator ++ ] = "<span class='free'></span>";
			}
		}

		// Show item as not available outside of timeframe timerange.
		for ($dayIterator; $dayIterator < count($days_display); $dayIterator++) {
			$days_display[ $dayIterator ] = "<span class='unavailable'></span>";
		}

		$dayStr         = implode( $divider, $days_display );
		$itemLink       = add_query_arg( 'location', $locationId, get_permalink( $item->ID ) );
		$locationString = '<div data-title="' . $locationName . '">' . $locationName . '</div>';
		return "<tr><td><b><a href='" . $itemLink . "'>" . $itemName . "</a></b>" . $divider . $locationString . $divider . $dayStr . "</td></tr>";
	}

	/**
	 * Returns calendar data
	 *
	 * @param null $item
	 * @param null $location
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getCalendarDataArray( $item, $location, $startDateString, $endDateString ) {
		if ( $item instanceof WP_Post || $item instanceof CustomPost ) {
			$item = $item->ID;
		}

		if ( $location instanceof WP_Post || $location instanceof CustomPost ) {
			$location = $location->ID;
		}

		if ( ! $item || ! $location ) {
			return [];
		}

		$startDate = new Day( $startDateString );
		$endDate   = new Day( $endDateString );

		$bookableTimeframes = \CommonsBooking\Repository\Timeframe::get(
			[ $location ],
			[ $item ],
			[ Timeframe::BOOKABLE_ID ],
			null,
			true,
			time()
		);

		if ( count( $bookableTimeframes ) ) {
			$closestBookableTimeframe = self::getClosestBookableTimeFrameForToday($bookableTimeframes);
			$advanceBookingDays = $closestBookableTimeframe->getFieldValue('timeframe-advance-booking-days' );

			// Check if start-/enddate was requested, then don't change it
			// otherwise start with current day
			$startDateTimestamp = time();
			if($closestBookableTimeframe->getStartDate() > $startDateTimestamp) {
				$startDateTimestamp = $closestBookableTimeframe->getStartDate();
			}

			if ( $startDateTimestamp > strtotime( $startDate->getDate() ) ) {
				$startDate = new Day( date( 'Y-m-d', $startDateTimestamp ) );
			}

			// Last day of month after next as default for calendar view
			// -> we just need to ensure, that pagination is possible
			$endDateTimestamp = self::getDefaultCalendarEnddateTimestamp($startDate);

			// get max advance booking days based on user defined max days in closest bookable timeframe
			$latestPossibleBookingDateTimestamp = $closestBookableTimeframe->getLatestPossibleBookingDateTimestamp();
			if($latestPossibleBookingDateTimestamp < $endDateTimestamp) {
				$endDateTimestamp = $latestPossibleBookingDateTimestamp;
			}

			$endDateString = date( 'Y-m-d', $endDateTimestamp );
			$endDate       = new Day( $endDateString );
		}

		return self::prepareJsonResponse( $startDate, $endDate, [ $location ], [ $item ], $advanceBookingDays);
	}

	/**
	 * Returns Last day of month after next as default for calendar view,
	 * based on $startDate param.
	 * @param $startDate
	 *
	 * @return false|int
	 */
	private static function getDefaultCalendarEnddateTimestamp($startDate) {
		return strtotime("last day of +3 months", $startDate->getDateObject()->getTimestamp());
	}

	/**
	 * Returns closest timeframe from date/time perspective.
	 * @param $bookableTimeframes
	 *
	 * @return \CommonsBooking\Model\Timeframe|null
	 */
	private static function getClosestBookableTimeFrameForToday($bookableTimeframes): ?\CommonsBooking\Model\Timeframe {
		// Sort timeframes by startdate
		usort( $bookableTimeframes, function ( $item1, $item2 ) {
			return $item1->getStartDate() < $item2->getStartDate();
		} );

		return array_pop( $bookableTimeframes );
	}

	/**
	 * Ajax request - Returns json-formatted calendardata.
	 * @throws Exception
	 */
	public static function getCalendarData() {
		// item by post-param
		$item = isset( $_POST['item'] ) && $_POST['item'] != "" ? sanitize_text_field( $_POST['item'] ) : false;
		if ( $item === false ) {
			throw new Exception( 'missing item id.' );
		}
		$location = isset( $_POST['location'] ) && $_POST['location'] != "" ? sanitize_text_field( $_POST['location'] ) : false;
		if ( $location === false ) {
			throw new Exception( 'missing location id.' );
		}

		// Ajax-Request param check
		if ( array_key_exists( 'sd', $_POST ) ) {
			$startDateString = sanitize_text_field( $_POST['sd'] );
		} else {
			throw new Exception( 'missing start date.' );
		}

		if ( array_key_exists( 'ed', $_POST ) ) {
			$endDateString = sanitize_text_field( $_POST['ed'] );
		} else {
			throw new Exception( 'missing end date.' );
		}

		$jsonResponse = Calendar::getCalendarDataArray( $item, $location, $startDateString, $endDateString );

		header( 'Content-Type: application/json' );
		echo json_encode( $jsonResponse );
		wp_die(); // All ajax handlers die when finished
	}

	/**
	 * Returns JSON-Data for Litepicker calendar.
	 *
	 * @param Day $startDate
	 * @param Day $endDate
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
		$advanceBookingDaysFormatted = null
	): array {
		$current_user   = wp_get_current_user();
		$customCacheKey = serialize( $current_user->roles );

		// we calculate the max advance booking days here to prepare the notice string in calender json.
		if($advanceBookingDaysFormatted == null) {
			$advanceBookingDays          = date_diff( $startDate->getDateObject(), $endDate->getDateObject() );
			$advanceBookingDaysFormatted = (int) $advanceBookingDays->format( '%a ' ) + 1;
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
				'advanceBookingDays'	  => $advanceBookingDaysFormatted,
			];

			// Notice with advanced booking days. Will be parsed in litepicker.js with DOM object #calendarNotice
			// TODO: deprecated 
			$jsonResponse['calendarNotice']['advanceBookingDays'] =
				//translators: %s = number of days
				commonsbooking_sanitizeHTML( sprintf( __( 'You can make bookings maximum %s days in advance', 'commonsbooking' ), $advanceBookingDaysFormatted ) );

			// renders pickup instruction info
			// deprecated since 2.6 due to template changes. pickup instructions now in location-info section
			// TODO: can be removed in next update > 2.6	
			if ( count( $locations ) === 1 ) {
				$jsonResponse['location']['fullDayInfo'] = nl2br(
					CB::get(
						'location',
						COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions',
						$locations[0]
					)
				);

				// are overbooking allowed in location options?
				$allowLockedDaysInRange                  = get_post_meta(
					$locations[0],
					COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range',
					true
				);
				$jsonResponse['disallowLockDaysInRange'] = $allowLockedDaysInRange !== 'on';
			}

			/** @var Week $week */
			foreach ( $calendar->getWeeks() as $week ) {
				/** @var Day $day */
				foreach ( $week->getDays() as $day ) {
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
						'lastSlotBooked'     => null
					];


					// If all slots are locked, day cannot be selected
					$allLocked = true;

					// If no slots are existing, day shall be locked
					$noSlots = true;

					// we process all slots and check status of each slot
					foreach ( $day->getGrid() as $slot ) {
						self::processSlot( $slot, $dayArray, $jsonResponse, $allLocked, $noSlots );
					}

					// If there are no slots defined, there's nothing bookable.
					if ( $noSlots || strtotime('today midnight') > strtotime( $day->getDate() ) ) {
						$dayArray['locked']    = true;
						$dayArray['holiday']   = false;
						$dayArray['repair']    = false;
						$dayArray['bookedDay'] = false;
					} else if ( count( $dayArray['slots'] ) === 1 ) {
						$timeframe           = $dayArray['slots'][0]['timeframe'];
						$dayArray['fullDay'] = get_post_meta( $timeframe->ID, 'full-day', true ) == "on";
					}

					// if day is out max advance booking days range, day is marked as locked to avoid booking
					if ( $day->getDate() > $endDate->getDate() ) {
						$dayArray['locked'] = true;
					}

					// Add day to calendar data.
					$jsonResponse['days'][ $day->getFormattedDate( 'Y-m-d' ) ] = $dayArray;

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
				}
			}

			// set transient expiration time to midnight to force cache refresh by daily basis to allow dynamic advanced booking day feature
			Plugin::setCacheItem( $jsonResponse, $customCacheKey, 'midnight' );
		}

		return $jsonResponse;
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
						$timeframeMaxDays        = get_post_meta( $slot['timeframe']->ID, 'timeframe-max-days', true );
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
			if ( ! in_array( $timeFrameType, [ Timeframe::LOCATION_CLOSED_ID ] ) ) {
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

			// Set partiallyBookedDay flag, if there is at least one slot that is of type bookedDay
			if ( in_array( $timeFrameType, [
				Timeframe::BOOKING_ID,
				\CommonsBooking\Model\Restriction::TYPE_REPAIR
			] ) ) {
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

}
