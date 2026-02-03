<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\BaseTestCase;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use SlopeIt\ClockMock\ClockMock;

abstract class CustomPostTypeTest extends BaseTestCase {

	/**
	 * This is the date that is used in the tests.
	 * It is a thursday.
	 */
	const CURRENT_DATE = '01.07.2021';

	const CURRENT_DATE_FORMATTED = 'July 1, 2021';

	/**
	 * The same date, but in Y-m-d format
	 * @var string
	 */
	protected string $dateFormatted;

	const USER_ID = 1;

	protected $locationId;

	protected $itemId;

	protected $firstTimeframeId;

	protected $secondTimeframeId;

	protected $bookingIds = [];

	protected $timeframeIds = [];

	protected $restrictionIds = [];

	protected $locationIds = [];

	protected $itemIds = [];

	protected $mapIds = [];

	protected $subscriberId;

	protected int $adminUserID;

	protected int $cbManagerUserID;
	protected int $editorUserID;

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
		$postAuthor = self::USER_ID,
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

		$this->timeframeIds[] = $timeframeId;

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
		$postAuthor = self::USER_ID,
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

		$this->restrictionIds[] = $restrictionId;

		return $restrictionId;
	}

	/**
	 * Creates booking from -1 day -> +1 day midnight (relative to self::CURRENT_DATE)
	 * @return int|\WP_Error
	 */
	protected function createConfirmedBookingEndingToday() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			$this->getEndOfDayTimestamp( self::CURRENT_DATE )
		);
	}

	/**
	 * Creates booking from -1 day -> +2 days midnight (relative to self::CURRENT_DATE)
	 * @return int|\WP_Error
	 */
	protected function createUnconfirmedBookingEndingTomorrow() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days midnight', strtotime( self::CURRENT_DATE ) ) - 1,
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
		$postAuthor = self::USER_ID,
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

		$this->bookingIds[] = $bookingId;

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

	protected function getEndOfDayTimestamp( $date ) {
		return strtotime( '+1 day midnight', strtotime( $date ) ) - 1;
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
			$locationId = $this->locationId;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemId;
		}

		return $this->createBooking(
			$locationId,
			$itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
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
			$locationId = $this->locationId;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemId;
		}

		return $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
	}

	protected function createHolidayTimeframeForAllItemsAndLocations() {
		$timeframe = $this->createTimeframe(
			$this->locationId,
			'',
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
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
			$locationId = $this->locationId;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemId;
		}
		$tf1 = $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+7 days', strtotime( self::CURRENT_DATE ) ),
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
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
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
			$locationId = $this->locationId;
		}
		if ( $itemId === null ) {
			$itemId = $this->itemId;
		}

		return $this->createTimeframe(
			$locationId,
			$itemId,
			strtotime( '+7 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+30 day', strtotime( self::CURRENT_DATE ) )
		);
	}

	// Create Item
	protected function createItem( $title, $postStatus = 'publish', $admins = [], $postAuthor = self::USER_ID ) {
		$itemId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Item::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		$this->itemIds[] = $itemId;

		if ( ! empty( $admins ) ) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}

	// Create Location
	protected function createLocation( $title, $postStatus = 'publish', $admins = [], $postAuthor = self::USER_ID ) {
		$locationId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Location::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		$this->locationIds[] = $locationId;

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
		$this->mapIds[] = $mapId;

		return $mapId;
	}

	/**
	 * We create the subscriber this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	protected function createSubscriber() {
		$wp_user = get_user_by( 'email', 'a@a.de' );
		if ( ! $wp_user ) {
			$this->subscriberId = wp_create_user( 'normaluser', 'normal', 'a@a.de' );
		} else {
			$this->subscriberId = $wp_user->ID;
		}
	}

	/**
	 * We create the administrator this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	public function createAdministrator() {
		$wp_user = get_user_by( 'email', 'admin@admin.de' );
		if ( ! $wp_user ) {
			$this->adminUserID = wp_create_user( 'adminuser', 'admin', 'admin@admin.de' );
			$user              = new \WP_User( $this->adminUserID );
			$user->set_role( 'administrator' );
		} else {
			$this->adminUserID = $wp_user->ID;
		}
	}

	/**
	 * We use this role to test assigning capabilities to other roles than the CBManager.
	 * @return void
	 */
	protected function createEditor() {
		$wp_user = get_user_by( 'email', 'editor@editor.de' );
		if ( ! $wp_user ) {
			$this->editorUserID = wp_create_user( 'editoruser', 'editor', 'editor@editor.de' );
			$user               = new \WP_User( $this->editorUserID );
			$user->set_role( 'editor' );
		} else {
			$this->editorUserID = $wp_user->ID;
		}
	}

	public function createCBManager() {
		// we need to run the functions that add the custom user role and assign it to the user
		Plugin::addCustomUserRoles();
		// and add the caps for each of our custom post types
		Plugin::addCPTRoleCaps();
		$wp_user = get_user_by( 'email', 'cbmanager@cbmanager.de' );
		if ( ! $wp_user ) {
			$this->cbManagerUserID = wp_create_user( 'cbmanager', 'cbmanager', 'cbmanager@cbmanager.de' );
			$user                  = new \WP_User( $this->cbManagerUserID );
			$user->set_role( Plugin::$CB_MANAGER_ID );
		} else {
			$this->cbManagerUserID = $wp_user->ID;
		}
	}

	protected function setUp(): void {
		parent::setUp();

		$this->dateFormatted = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );

		$this->setUpBookingCodesTable();

		// Create location
		$this->locationId = self::createLocation( 'Testlocation', 'publish' );

		// Create Item
		$this->itemId = self::createItem( 'TestItem', 'publish' );
	}

	protected function setUpBookingCodesTable() {
		global $wpdb;
		$table_name      = $wpdb->prefix . BookingCodes::$tablename;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code)
        ) $charset_collate;";

		$wpdb->query( $sql );
	}

	protected function tearDown(): void {
		parent::tearDown();

		ClockMock::reset();
		$this->tearDownAllItems();
		$this->tearDownAllLocation();
		$this->tearDownAllTimeframes();
		$this->tearDownAllBookings();
		$this->tearDownAllRestrictions();
		$this->tearDownAllMaps();
		$this->tearDownBookingCodesTable();

		wp_logout();
	}

	protected function tearDownAllLocation() {
		foreach ( $this->locationIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllItems() {
		foreach ( $this->itemIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllTimeframes() {
		foreach ( $this->timeframeIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllBookings() {
		foreach ( $this->bookingIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllRestrictions() {
		foreach ( $this->restrictionIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllMaps() {
		foreach ( $this->mapIds as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$wpdb->query( $sql );
	}
}
