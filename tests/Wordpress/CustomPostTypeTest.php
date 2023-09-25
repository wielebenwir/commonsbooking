<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;

abstract class CustomPostTypeTest extends TestCase {

	const CURRENT_DATE = '01.07.2021';

	const CURRENT_DATE_FORMATTED = 'July 1, 2021';

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

	protected $subscriberId;

	protected int $adminUserID;

	protected int $cbManagerUserID;

	protected function createTimeframe(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$type = Timeframe::BOOKABLE_ID,
		$fullday = "on",
		$repetition = 'w',
		$grid = 0,
		$startTime = '8:00 AM',
		$endTime = '12:00 PM',
		$postStatus = 'publish',
		$weekdays = [ "1", "2", "3", "4", "5", "6", "7" ],
		$postAuthor = self::USER_ID,
		$maxDays = 3,
		$advanceBookingDays = 30,
		$bookingStartdayOffset = 0,
		$showBookingCodes = "on",
		$createBookingCodes = "on",
		$postTitle = 'TestTimeframe'
	) {
		// Create Timeframe
		$timeframeId = wp_insert_post( [
			'post_title'  => $postTitle,
			'post_type'   => Timeframe::$postType,
			'post_status' => $postStatus,
			'post_author' => $postAuthor
		] );

		update_post_meta( $timeframeId, 'type', $type );
		update_post_meta( $timeframeId, 'location-id', $locationId );
		update_post_meta( $timeframeId, 'item-id', $itemId );
		update_post_meta( $timeframeId, 'timeframe-max-days', $maxDays );
		update_post_meta( $timeframeId, 'timeframe-advance-booking-days', $advanceBookingDays );
		update_post_meta( $timeframeId, 'booking-startday-offset', $bookingStartdayOffset );
		update_post_meta( $timeframeId, 'full-day', $fullday );
		update_post_meta( $timeframeId, 'timeframe-repetition', $repetition );
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
		update_post_meta( $timeframeId, 'show-booking-codes', $showBookingCodes );
		update_post_meta( $timeframeId, 'create-booking-codes', $createBookingCodes );

		$this->timeframeIds[] = $timeframeId;

		return $timeframeId;
	}

	protected function createRestriction(
		$restrictionType,
		$locationId,
		$itemId,
		$start,
		$end,
		$state = "active",
		$hint = "Hint",
		$postAuthor = self::USER_ID,
		$postTitle = "Restriction",
		$postStatus = "publish"
	) {
		//	create restriction
		$restrictionId = wp_insert_post( [
			'post_title'  => $postTitle,
			'post_type'   => Restriction::$postType,
			'post_status' => $postStatus,
			'post_author' => $postAuthor
		] );

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

	protected function createConfirmedBookingEndingToday() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			$this->getEndOfDayTimestamp( self::CURRENT_DATE )
		);
	}

	protected function createUnconfirmedBookingEndingTomorrow() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days midnight', strtotime( self::CURRENT_DATE  ) ) - 1,
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
		$startTime = '8:00 AM',
		$endTime = '12:00 PM',
		$postStatus = 'confirmed',
		$postAuthor = self::USER_ID,
		$timeframeRepetition = 'w',
		$timeframeMaxDays = 3,
		$postTitle = 'Booking',
		$grid = 0,
		$weekdays = [ "1", "2", "3", "4", "5", "6", "7" ]
	) {
		// Create booking
		$bookingId = wp_insert_post( [
			'post_title'  => $postTitle,
			'post_type'   => Booking::$postType,
			'post_status' => $postStatus,
			'post_author' => $postAuthor
		] );

		update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, 'timeframe-repetition', $timeframeRepetition );
		update_post_meta( $bookingId, 'start-time', $startTime );
		update_post_meta( $bookingId, 'end-time', $endTime );
		update_post_meta( $bookingId, 'timeframe-max-days', $timeframeMaxDays );
		update_post_meta( $bookingId, 'location-id', $locationId );
		update_post_meta( $bookingId, 'item-id', $itemId );
		update_post_meta( $bookingId, 'grid', $grid );
		update_post_meta( $bookingId, 'repetition-start', $repetitionStart );
		update_post_meta( $bookingId, 'repetition-end', $repetitionEnd );
		update_post_meta( $bookingId, 'weekdays', $weekdays );

		$this->bookingIds[] = $bookingId;

		return $bookingId;
	}

	protected function getEndOfDayTimestamp( $date ) {
		return strtotime( '+1 day midnight', strtotime( $date ) ) - 1;
	}

	protected function createConfirmedBookingStartingToday() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
	}

	protected function createBookableTimeFrameIncludingCurrentDay($locationId = null, $itemId = null) {
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
	protected function createTwoBookableTimeframeSlotsIncludingCurrentDay( $locationId = null, $itemId = null): array {
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
			"publish",
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
			"publish",
			'',
		);
		return [ $tf1, $tf2 ];
	}

	protected function createBookableTimeFrameStartingInAWeek($locationId = null, $itemId = null) {
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
	protected function createItem($title, $postStatus, $admins = []) {
		$itemId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Item::$postType,
			'post_status' => $postStatus
		] );

		$this->itemIds[] = $itemId;

		if (! empty($admins)) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}

	// Create Location
	protected function createLocation($title, $postStatus, $admins = []) {
		$locationId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Location::$postType,
			'post_status' => $postStatus
		] );

		$this->locationIds[] = $locationId;

		if (! empty($admins)) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		return $locationId;
	}

	protected function createMap($options) {
		$mapId = wp_insert_post( [
			'post_title'  => 'Map',
			'post_type'   => Map::$postType,
			'post_status' => 'publish'
		] );

		update_post_meta( $mapId, 'cb_map_options', $options );

		return $mapId;
	}

	/**
	 * We create the subscriber this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	protected function createSubscriber(){
		$wp_user = get_user_by('email',"a@a.de");
		if (! $wp_user){
			$this->subscriberId = wp_create_user("normaluser","normal","a@a.de");
		}
		else {
			$this->subscriberId = $wp_user->ID;
		}
	}

	/**
	 * We create the administrator this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	public function createAdministrator(){
		$wp_user = get_user_by('email',"admin@admin.de");
		if (! $wp_user) {
			$this->adminUserID = wp_create_user( "adminuser", "admin", "admin@admin.de" );
			$user = new \WP_User( $this->adminUserID );
			$user->set_role( 'administrator' );
		}
		else {
			$this->adminUserID = $wp_user->ID;
		}
	}

	public function createCBManager(){
		//we need to run the functions that add the custom user role and assign it to the user
		Plugin::addCustomUserRoles();
		//and add the caps for each of our custom post types
		$postTypes = Plugin::getCustomPostTypes();
		foreach ($postTypes as $customPostType) {
			Plugin::addRoleCaps($customPostType::$postType);
		}
		$wp_user = get_user_by('email',"cbmanager@cbmanager.de");
		if (! $wp_user) {
			$this->cbManagerUserID = wp_create_user( "cbmanager", "cbmanager", "cbmanager@cbmanager.de" );
			$user = new \WP_User( $this->cbManagerUserID );
			$user->set_role( Plugin::$CB_MANAGER_ID );
		}
		else {
			$this->cbManagerUserID = $wp_user->ID;
		}
	}

  protected function setUp() : void {
        parent::setUp();

		$this->setUpBookingCodesTable();

		// Create location
		$this->locationId = self::createLocation('Testlocation', 'publish');

		// Create Item
		$this->itemId = self::createItem('TestItem', 'publish');
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

	protected function tearDown() : void {
		parent::tearDown();

		ClockMock::reset();
		$this->tearDownAllItems();
		$this->tearDownAllLocation();
		$this->tearDownAllTimeframes();
		$this->tearDownAllBookings();
		$this->tearDownAllRestrictions();
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

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$wpdb->query( $sql );
	}

}
