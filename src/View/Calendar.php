<?php


namespace CommonsBooking\View;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
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
	 * @param $atts array Supports the following attributes:
	 *                    - locationcat: Filter by location category
	 *                    - itemcat: Filter by item category
	 *                    - days: Number of days to show in calendar table view
	 *                    - desc: Description text
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
		$date  = Wordpress::getUTCDateTimeByTimestamp( current_time( 'timestamp' ) );
		$today = $date->format( 'Y-m-d' );

		$days_display = array_fill( 0, $days, 'n' );
		$days_cols    = array_fill( 0, $days, '<col>' );
		$month        = date( 'Y-m' );
		$month_cols   = [ $month => 0 ];
		$days_dates   = [];

		for ( $i = 0; $i < $days; $i++ ) {
			++$month_cols[ $month ];
			$days_display[ $i ] = $date->format( 'd' );
			$days_dates[ $i ]   = $date->format( 'Y-m-d' );

			if ( $date->format( 'N' ) >= 7 ) {
				$days_cols[ $i ] = '<col class="bg_we">';
			}
			$date->modify( '+1 day' );
			if ( $date->format( 'Y-m' ) != $month ) {
				$month                = $date->format( 'Y-m' );
				$month_cols[ $month ] = 0;
			}
		}

		$last_day = $days_dates[ $days - 1 ];
		$colStr   = implode( ' ', $days_cols );

		$print  = '<div class="cb-table-scroll">';
		$print .= "<table class='cb-items-table tablesorter'><colgroup><col><col>" . $colStr . '</colgroup><thead>';
		// Use td-tag when no table header description is given, to match semantics of header cells
		$accessible_table_header_tag = empty( $desc ) ? 'td' : 'th';
		$print                      .= "<tr><$accessible_table_header_tag colspan='2' class='sortless'>" . $desc . "</$accessible_table_header_tag>";

		// Render months
		$print .= self::renderHeadlineMonths( $month_cols );
		$print .= '</tr>';

		// Render Headline Days
		$print .= '<tr>';
		$print .= self::renderHeadlineDays( $days_display );
		$print .= '</tr></thead><tbody>';

		$items = get_posts(
			array(
				'post_type'      => 'cb_item',
				'post_status'    => 'publish',
				'order'          => $atts['order'] ?? 'ASC',
				'orderby'        => $atts['orderby'] ?? 'post_title',
				'posts_per_page' => - 1,
			)
		);

		$itemRowsHTML = '';

		foreach ( $items as $item ) {
			// Check for category term
			if ( $itemCategory ) {
				if ( ! has_term( $itemCategory, Item::getTaxonomyName(), $item->ID ) ) {
					continue;
				}
			}

			$rowHtml = ' ';

			// Get timeframes for item
			$timeframes = \CommonsBooking\Repository\Timeframe::getInRangeForCurrentUser(
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
					// TODO #507
					$locations[ $timeframe->getLocationID() ] = $timeframe->getLocation()->post_title;
				}

				// loop through location
				foreach ( $locations as $locationId => $locationName ) {
					$customCacheKey = $item->ID . $locationId . $today;
					$cacheItem      = Plugin::getCacheItem( $customCacheKey );
					if ( $cacheItem ) {
						$rowHtml .= $cacheItem;
					} else {
						// Check for category term
						if ( $locationCategory ) {
							if ( ! has_term( $locationCategory, Location::getTaxonomyName(), $locationId ) ) {
								continue;
							}
						}

						$locationHtml = self::renderItemLocationRow( $item, $locationId, $locationName, $today, $last_day, $days, $days_display );
						Plugin::setCacheItem( $locationHtml, [ strval( $item->ID ), strval( $locationId ) ], $customCacheKey );
						$rowHtml .= $locationHtml;
					}
				}
				$itemRowsHTML .= $rowHtml;
			}
		}

		if ( empty( $itemRowsHTML ) ) { // print message of unavailable items
			$print .= '<tr style="color: var(--commonsbooking-color-error);"><td colspan="2">' . __( 'No items found.', 'commonsbooking' ) . '</td></tr>';
		} else { // if there are item rows, append them to the table
			$print .= $itemRowsHTML;
		}

		$print .= '</tbody></table>';
		$print .= '</div>';

		return $print;
	}

	public static function shortcode( $atts ) {
		global $templateData;
		$templateData         = [];
		$templateData['data'] = self::renderTable( $atts );

		if ( ! empty( $templateData['data'] ) ) {
			ob_start();
			commonsbooking_get_template_part( 'shortcode', 'items_table', true, false, false );
			return ob_get_clean();
		}
	}

	/**
	 * Renders months in headline.
	 *
	 * @param $month_cols
	 *
	 * @return string
	 */
	protected static function renderHeadlineMonths( $month_cols ): string {
		$print = '';
		foreach ( $month_cols as $month => $colspan ) {
			$print .= "<th class='sortless' colspan='" . $colspan . "'>";

			if ( $colspan > 3 ) {
				$print .= date_i18n( 'F', strtotime( get_date_from_gmt( $month ) ) ) . '</th>';
			} else {
				$print .= date_i18n( 'M', strtotime( get_date_from_gmt( $month ) ) ) . '</th>';
			}
		}

		return $print;
	}

	/**
	 * Renders days in headline.
	 *
	 * @param $days_display
	 *
	 * @return string
	 */
	protected static function renderHeadlineDays( $days_display ): string {
		$divider = "</th><th class='cal sortless'>";
		$dayStr  = implode( $divider, $days_display );

		return '<th>' . __( 'Item', 'commonsbooking' ) . '</th>' .
				'<th>' . __( 'Location', 'commonsbooking' ) . "<th class='cal sortless'>" . $dayStr . '</th>';
	}

	/**
	 * Renders item/location row.
	 *
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
	protected static function renderItemLocationRow( $item, $locationId, $locationName, $today, $last_day, $days, $days_display ): string {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$divider  = '</td><td>';
			$itemName = $item->post_title;

			// Get data for current item/location combination
			$calendarData = self::getCalendarDataArray(
				$item->ID,
				$locationId,
				$today,
				date( 'Y-m-d', strtotime( '+' . $days . ' days', time() ) ),
				true
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
					$days_display[ $dayIterator++ ] = "<span class='is-locked'></span>";
				} elseif ( $data['holiday'] ) {
					$days_display[ $dayIterator++ ] = "<span class='is-holiday'></span>";
				} elseif ( $data['locked'] && $data['firstSlotBooked'] && $data['lastSlotBooked'] ) {
					$days_display[ $dayIterator++ ] = "<span class='is-booked'></span>";
				} elseif ( $data['locked'] && $data['partiallyBookedDay'] ) {
					$cssClass = 'is-partially-booked-end';
					if ( ! $data['firstSlotBooked'] && $data['lastSlotBooked'] ) {
						$cssClass = 'is-partially-booked-start';
					}
					$days_display[ $dayIterator++ ] = "<span class='$cssClass'></span>";
				} elseif ( $data['locked'] ) {
					$days_display[ $dayIterator++ ] = "<span class='is-locked'></span>";
				} else {
					$days_display[ $dayIterator++ ] = '<span></span>';
				}

				// Stop when enddate (advanced booking days limit) is reached
				if ( $day == $calendarData['endDate'] ) {
					break;
				}
			}

			// Show item as not available outside of timeframe timerange.
			for ( $dayIterator; $dayIterator < count( $days_display ); $dayIterator++ ) {
				$days_display[ $dayIterator ] = "<span'is-locked'></span>";
			}

			$dayStr         = implode( $divider, $days_display );
			$itemLink       = add_query_arg( 'cb-location', $locationId, get_permalink( $item->ID ) );
			$locationString = '<div data-title="' . $locationName . '">' . $locationName . '</div>';
			$locationLink   = get_permalink( $locationId );

			$rowHtml = "<tr><td><b><a href='" . $itemLink . "'>" . $itemName . '</a></b>' . $divider . $locationString . $divider . $dayStr . '</td></tr>';
			Plugin::setCacheItem( $rowHtml, [ $locationId, $item->ID ] );

			return $rowHtml;
		}
	}

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
	 * The value for the amount of months shown in the Litepicker mobile view portrait mode.
	 * Fixes #1103, an issue where one instance has issues with switching the months on mobile.
	 * This value is configurable through a filter hook only.
	 *
	 * Default value is 1.
	 *
	 * @return int
	 */
	private static function getMobileCalendarMonthCount(): int {
		$month = 1;
		/**
		 * Default amount of months shown in the Litepicker mobile view portrait mode.
		 *
		 * @since 2.10.3
		 *
		 * @param int $month defaults is 1
		 */
		return apply_filters( 'commonsbooking_mobile_calendar_month_count', $month );
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
	 * @param Day $day
	 *
	 * @return array
	 */
	protected static function getLockedDayArray( Day $day ): array {
		return [
			'date'               => $day->getFormattedDate( 'd.m.Y' ),
			'slots'              => [],
			'locked'             => true,
			'bookedDay'          => false,
			'partiallyBookedDay' => false,
			'holiday'            => false,
			'repair'             => false,
			'fullDay'            => false,
			'firstSlotBooked'    => false,
			'lastSlotBooked'     => false,
		];
	}

	/**
	 * Ajax request - Returns json-formatted calendardata.
	 *
	 * @throws Exception
	 */
	public static function getCalendarData() {
		// item by post-param
		$item = isset( $_POST['item'] ) && $_POST['item'] != '' ? intval( $_POST['item'] ) : false;
		if ( $item === false || $item == 0 ) { // 0 = failed intval check
			throw new Exception( 'missing item id.' );
		}

		// location by post-param
		$location = isset( $_POST['location'] ) && $_POST['location'] != '' ? intval( $_POST['location'] ) : false;
		if ( $location === false || $location == 0 ) { // 0 = failed intval check
			throw new Exception( 'missing location id.' );
		}

		// Ajax-Request param check
		if ( array_key_exists( 'sd', $_POST ) && Wordpress::isValidDateString( $_POST['sd'] ) ) {
			$startDateString = sanitize_text_field( $_POST['sd'] );
		} else {
			throw new Exception( 'wrong or missing start date.' );
		}

		if ( array_key_exists( 'ed', $_POST ) && Wordpress::isValidDateString( $_POST['ed'] ) ) {
			$endDateString = sanitize_text_field( $_POST['ed'] );
		} else {
			throw new Exception( 'wrong or missing end date.' );
		}

		$jsonResponse = self::getCalendarDataArray( $item, $location, $startDateString, $endDateString );

		header( 'Content-Type: application/json' );
		echo wp_json_encode( $jsonResponse );
		wp_die(); // All ajax handlers die when finished
	}
}
