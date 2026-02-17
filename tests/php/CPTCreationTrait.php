<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
trait CPTCreationTrait {
	protected $locationID;
	protected $itemID;
	protected $bookingIDs = [];

	protected $timeframeIDs = [];

	protected $restrictionIDs = [];

	protected $locationIDs = [];

	protected $itemIDs = [];

	protected $mapIDs = [];

	protected function createTimeframe(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$type = Timeframe::BOOKABLE_ID,
		$fullday = 'on',
		$repetition = 'w',
		$grid = 0,
		$startTime = '8:00 AM',
		$endTime = '12:00 PM',
		$postStatus = 'publish',
		$weekdays = [ '1', '2', '3', '4', '5', '6', '7' ],
		$manualSelectionDays = '',
		$postAuthor = \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID,
		$maxDays = 3,
		$advanceBookingDays = 30,
		$bookingStartdayOffset = 0,
		$showBookingCodes = 'on',
		$createBookingCodes = 'on',
		$postTitle = 'TestTimeframe'
	) {
		// Create Timeframe
		$timeframeId = wp_insert_post(
			[
				'post_title'  => $postTitle,
				'post_type'   => Timeframe::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		update_post_meta( $timeframeId, 'type', $type );
		// we need to map the multi-location array and multi-item array on a string array because that is the way it is also saved from the WP-backend
		if ( is_array( $locationId ) ) {
			update_post_meta(
				$timeframeId,
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST,
				array_map( 'strval', $locationId )
			);
		} else {
			update_post_meta(
				$timeframeId,
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID,
				$locationId
			);
		}
		if ( is_array( $itemId ) ) {
			update_post_meta(
				$timeframeId,
				\CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST,
				array_map( 'strval', $itemId )
			);
		} else {
			update_post_meta(
				$timeframeId,
				\CommonsBooking\Model\Timeframe::META_ITEM_ID,
				$itemId
			);
		}
		update_post_meta( $timeframeId, 'timeframe-max-days', $maxDays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, $advanceBookingDays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET, $bookingStartdayOffset );
		update_post_meta( $timeframeId, 'full-day', $fullday );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_REPETITION, $repetition );
		if ( $repetitionStart ) {
			update_post_meta( $timeframeId, 'repetition-start', $repetitionStart );
		}
		if ( $repetitionEnd ) {
			update_post_meta( $timeframeId, 'repetition-end', $repetitionEnd );
		}
		update_post_meta( $timeframeId, 'start-time', $startTime );
		update_post_meta( $timeframeId, 'end-time', $endTime );
		update_post_meta( $timeframeId, 'grid', $grid );
		update_post_meta( $timeframeId, 'weekdays', $weekdays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_MANUAL_SELECTION, $manualSelectionDays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES, $showBookingCodes );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES, $createBookingCodes );
		// TODO: Make this value configurable
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );

		$this->timeframeIDs[] = $timeframeId;

		return $timeframeId;
	}

	protected function createRestriction(
		$restrictionType,
		$locationId,
		$itemId,
		$start,
		$end,
		$state = 'active',
		$hint = 'Hint',
		$postAuthor = \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID,
		$postTitle = 'Restriction',
		$postStatus = 'publish'
	) {
		// create restriction
		$restrictionId = wp_insert_post(
			[
				'post_title'  => $postTitle,
				'post_type'   => Restriction::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_TYPE, $restrictionType );
		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_LOCATION_ID, $locationId );
		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_ITEM_ID, $itemId );
		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_HINT, $hint );
		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_START, $start );
		if ( $end ) {
			update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_END, $end );
		}
		update_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_STATE, $state );

		$this->restrictionIDs[] = $restrictionId;

		return $restrictionId;
	}

	/**
	 * Creates booking from -1 day -> +1 day midnight (relative to self::CURRENT_DATE)
	 * @return int|\WP_Error
	 */
	protected function createConfirmedBookingEndingToday() {
		return $this->createBooking(
			$this->locationID,
			$this->itemID,
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			$this->getEndOfDayTimestamp( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE )
		);
	}

	protected function getEndOfDayTimestamp( $date ) {
		return strtotime( '+1 day midnight', strtotime( $date ) ) - 1;
	}

	/**
	 * Creates booking from -1 day -> +2 days midnight (relative to self::CURRENT_DATE)
	 * @return int|\WP_Error
	 */
	protected function createUnconfirmedBookingEndingTomorrow() {
		return $this->createBooking(
			$this->locationID,
			$this->itemID,
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+2 days midnight', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ) - 1,
			null,
			null,
			'unconfirmed'
		);
	}

	protected function createBooking(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$startTime = '12:00 AM',
		$endTime = '23:59',
		$postStatus = 'confirmed',
		$postAuthor = \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID,
		$timeframeRepetition = 'w',
		$timeframeMaxDays = 3,
		$postTitle = 'Booking',
		$grid = 0,
		$weekdays = [ '1', '2', '3', '4', '5', '6', '7' ],
		$startGridSize = '', // How long is the timeframe in which the booking starts
		$endGridSize = '' // How long is the timeframe in which the booking ends
	) {
		// Create booking
		$bookingId = wp_insert_post(
			[
				'post_title'  => $postTitle,
				'post_type'   => Booking::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, \CommonsBooking\Model\Timeframe::META_REPETITION, $timeframeRepetition );
		update_post_meta( $bookingId, 'start-time', $startTime );
		update_post_meta( $bookingId, 'end-time', $endTime );
		update_post_meta( $bookingId, 'timeframe-max-days', $timeframeMaxDays );
		update_post_meta( $bookingId, 'location-id', $locationId );
		update_post_meta( $bookingId, 'item-id', $itemId );
		update_post_meta( $bookingId, 'grid', $grid );
		update_post_meta( $bookingId, 'repetition-start', $repetitionStart );
		update_post_meta( $bookingId, 'repetition-end', $repetitionEnd );
		update_post_meta( $bookingId, 'weekdays', $weekdays );

		if ( $startGridSize ) {
			update_post_meta( $bookingId, \CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE, $startGridSize );
		}
		if ( $endGridSize ) {
			update_post_meta( $bookingId, \CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE, $endGridSize );
		}

		$this->bookingIDs[] = $bookingId;

		return $bookingId;
	}

	/**
	 * This method is Unit Test specific. Because we need to flush the cache after cancelling.
	 *
	 * @param \CommonsBooking\Model\Booking $b
	 *
	 * @return void
	 */
	protected function cancelBooking( \CommonsBooking\Model\Booking $b ) {
		$b->cancel();
		// flush cache to reflect updated post
		wp_cache_flush();
	}

	/**
	 * Creates booking from midnight -> +2 days (relative to self::CURRENT_DATE)
	 *
	 * @param $locationId
	 * @param $itemId
	 *
	 * @return int|\WP_Error
	 */
	protected function createConfirmedBookingStartingToday( $locationId = null, $itemId = null ) {
		if ( $locationId === null ) {
			$locationId = $this->locationID;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemID;
		}

		return $this->createBooking(
			$locationId,
			$itemId,
			strtotime( 'midnight', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) )
		);
	}

	/**
	 * Creates timeframe from -1 day -> +1 day (relative to self::CURRENT_DATE)
	 *
	 * @param $locationId
	 * @param $itemId
	 *
	 * @return int|\WP_Error
	 */
	protected function createBookableTimeFrameIncludingCurrentDay( $locationId = null, $itemId = null ) {
		if ( $locationId === null ) {
			$locationId = $this->locationID;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemID;
		}

		return $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) )
		);
	}

	protected function createHolidayTimeframeForAllItemsAndLocations() {
		$timeframe = $this->createTimeframe(
			$this->locationID,
			'',
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			Timeframe::HOLIDAYS_ID,
		);

		// now, let's set our timeframe to be assigned to all items
		update_post_meta(
			$timeframe,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_ALL_ID
		);
		update_post_meta(
			$timeframe,
			\CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_ALL_ID
		);
		// and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta( $timeframe );

		return $timeframe;
	}

	/**
	 * Will create two timeframes for the same item / location combination on the same day spanning over a week.
	 *
	 * The two slots for the timeframes go from 10:00 - 15:00 and from 15:00 to 18:00.
	 *
	 * @param null $locationId
	 * @param null $itemId
	 *
	 * @return array An array where the first element is the 10:00-15:00 timeframe and the second is the 15:00 - 18:00 timeframe.
	 */
	protected function createTwoBookableTimeframeSlotsIncludingCurrentDay( $locationId = null, $itemId = null ): array {
		if ( $locationId === null ) {
			$locationId = $this->locationID;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemID;
		}
		$tf1 = $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+7 days', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'',
			'd',
			0,
			'10:00',
			'15:00',
			'publish',
			'',
		);
		$tf2 = $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'',
			'd',
			0,
			'15:00',
			'18:00',
			'publish',
			'',
		);

		return [ $tf1, $tf2 ];
	}

	/**
	 * Creates timeframe from +7 days -> +30 days (relative to self::CURRENT_DATE)
	 *
	 * @param $locationId
	 * @param $itemId
	 *
	 * @return int|\WP_Error
	 */
	protected function createBookableTimeFrameStartingInAWeek( $locationId = null, $itemId = null ) {
		if ( $locationId === null ) {
			$locationId = $this->locationID;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemID;
		}

		return $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '+7 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+30 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) )
		);
	}

	// Create Item
	protected function createItem( $title, $postStatus = 'publish', $admins = [], $postAuthor = \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID ) {
		$itemId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Item::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		$this->itemIDs[] = $itemId;

		if ( ! empty( $admins ) ) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}

	// Create Location
	protected function createLocation( $title, $postStatus = 'publish', $admins = [], $postAuthor = \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID ) {
		$locationId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Location::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		$this->locationIDs[] = $locationId;

		if ( ! empty( $admins ) ) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		return $locationId;
	}

	protected function createMap() {
		$mapId = wp_insert_post(
			[
				'post_title'  => 'Map',
				'post_type'   => Map::$postType,
				'post_status' => 'publish',
			]
		);

		// setup map in new format
		$defaultValues = array_reduce(
			Map::getCustomFields(),
			function ( $result, $option ) {
				if ( isset( $option['default'] ) ) {
					$result[ $option['id'] ] = $option['default'];
				}

				return $result;
			},
			array()
		);
		foreach ( $defaultValues as $key => $value ) {
			update_post_meta( $mapId, $key, $value );
		}
		$this->mapIDs[] = $mapId;

		return $mapId;
	}
}
