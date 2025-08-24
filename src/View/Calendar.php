<?php


namespace CommonsBooking\View;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Plugin;
use CommonsBooking\Service\CalendarService;
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
			$calendarData = CalendarService::getCalendarDataArray(
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
		 * @since 2.10.5
		 *
		 * @param int $month defaults is 1
		 */
		return apply_filters( 'commonsbooking_mobile_calendar_month_count', $month );
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

		$jsonResponse = CalendarService::getCalendarDataArray( $item, $location, $startDateString, $endDateString );

		header( 'Content-Type: application/json' );
		echo wp_json_encode( $jsonResponse );
		wp_die(); // All ajax handlers die when finished
	}
}
