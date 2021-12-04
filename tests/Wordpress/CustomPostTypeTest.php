<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;

abstract class CustomPostTypeTest extends TestCase {

	const CURRENT_DATE = '01.07.2021';

	const USER_ID = 1;

	protected $locationId;

	protected $itemId;

	protected $firstTimeframeId;

	protected $secondTimeframeId;

	protected $bookingIds = [];

	protected $timeframeIds = [];

	protected $restrictionIds = [];

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
		$state = "1",
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

		update_post_meta( $restrictionId, "restriction-type", $restrictionType );
		update_post_meta( $restrictionId, "restriction-location-id", $locationId );
		update_post_meta( $restrictionId, "restriction-item-id", $itemId );
		update_post_meta( $restrictionId, "restriction-hint", $hint );
		update_post_meta( $restrictionId, "restriction-start", $start );
		update_post_meta( $restrictionId, "restriction-end", $end );
		update_post_meta( $restrictionId, "restriction-state", $state );

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

	protected function createBookableTimeFrameIncludingCurrentDay() {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
	}

	protected function setUp() {
		parent::setUp();

		$this->setUpBookingCodesTable();

		// Create location
		$this->locationId = wp_insert_post( [
			'post_title'  => 'TestLocation',
			'post_type'   => Location::$postType,
			'post_status' => 'publish'
		] );

		// Create Item
		$this->itemId = wp_insert_post( [
			'post_title'  => 'TestItem',
			'post_type'   => Item::$postType,
			'post_status' => 'publish'
		] );
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

	protected function tearDown() {
		parent::tearDown();
		wp_delete_post( $this->itemId, true );
		wp_delete_post( $this->locationId, true );

		$this->tearDownAllTimeframes();
		$this->tearDownAllBookings();
		$this->tearDownAllRestrictions();
		$this->tearDownBookingCodesTable();
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
