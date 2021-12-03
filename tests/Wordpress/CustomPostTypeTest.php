<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;

abstract class CustomPostTypeTest extends TestCase {

	const CURRENT_DATE = '01.07.2021';

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	protected $locationId;

	protected $itemId;

	protected $timeframeId;

	protected $firstTimeframeId;

	protected $secondTimeframeId;
	
	protected $bookingIds = [];
	
	protected $timeframeIds = [];

	protected function getEndOfDayTimestamp($date) {
		return strtotime('+1 day midnight',strtotime($date)) - 1;
	}

	protected function createConfirmedBookingEndingToday() {
		// Create Timeframe
		$bookingId = wp_insert_post( [
			'post_title'   => 'Booking ending today',
			'post_type'=> Booking::$postType,
			'post_status' => 'confirmed'
		] );

		update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, 'timeframe-repetition', 'w');
		update_post_meta( $bookingId, 'start-time','8:00 AM');
		update_post_meta( $bookingId, 'end-time', '12:00 PM');
		update_post_meta( $bookingId, 'timeframe-max-days', '3');
		update_post_meta( $bookingId, 'location-id', $this->locationId);
		update_post_meta( $bookingId, 'item-id', $this->itemId);
		update_post_meta( $bookingId, 'grid','0');
		update_post_meta( $bookingId, 'repetition-start', strtotime('-1 day', strtotime(self::CURRENT_DATE)));
		update_post_meta( $bookingId, 'repetition-end', $this->getEndOfDayTimestamp(self::CURRENT_DATE));
		update_post_meta( $bookingId,
			'weekdays',
			[ "1", "2", "3", "4", "5", "6" ]
		);

		$this->bookingIds[] = $bookingId;
	}

	protected function createConfirmedBookingStartingToday() {
		// Create Timeframe
		$bookingId = wp_insert_post( [
			'post_title'   => 'Booking ending today',
			'post_type'=> Booking::$postType,
			'post_status' => 'confirmed'
		] );

		update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, 'timeframe-repetition', 'w');
		update_post_meta( $bookingId, 'start-time','8:00 AM');
		update_post_meta( $bookingId, 'end-time', '12:00 PM');
		update_post_meta( $bookingId, 'timeframe-max-days', '3');
		update_post_meta( $bookingId, 'location-id', $this->locationId);
		update_post_meta( $bookingId, 'item-id', $this->itemId);
		update_post_meta( $bookingId, 'grid','0');
		update_post_meta( $bookingId, 'repetition-start', strtotime('midnight', strtotime(self::CURRENT_DATE)));
		update_post_meta( $bookingId, 'repetition-end', strtotime('+2 days', strtotime(self::CURRENT_DATE)));
		update_post_meta( $bookingId,
			'weekdays',
			[ "1", "2", "3", "4", "5", "6" ]
		);
		$this->bookingIds[] = $bookingId;
	}

	protected function createBookableTimeFrameIncludingCurrentDay() {
		// Create Timeframe
		$this->timeframeId = wp_insert_post( [
			'post_title'   => 'TestTimeframe',
			'post_type'=> Timeframe::$postType,
			'post_status' => 'publish'
		] );

		update_post_meta( $this->timeframeId, 'type', Timeframe::BOOKABLE_ID );
		update_post_meta( $this->timeframeId, 'timeframe-repetition', 'w');
		update_post_meta( $this->timeframeId, 'start-time','8:00 AM');
		update_post_meta( $this->timeframeId, 'end-time', '12:00 PM');
		update_post_meta( $this->timeframeId, 'timeframe-max-days', '3');
		update_post_meta( $this->timeframeId, 'location-id', $this->locationId);
		update_post_meta( $this->timeframeId, 'item-id', $this->itemId);
		update_post_meta( $this->timeframeId, 'grid','0');
		update_post_meta( $this->timeframeId, 'repetition-start', strtotime('-1 day', strtotime(self::CURRENT_DATE)));
		update_post_meta( $this->timeframeId, 'repetition-end', strtotime('+1 day', strtotime(self::CURRENT_DATE)));
		update_post_meta( $this->timeframeId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);
		$this->timeframeIds[] = $this->timeframeId;
	}

	/**
	 * Timeframe with enddate.
	 */
	protected function createBookableTimeFrameWithEnddate() {
		// Create Timeframe
		$this->firstTimeframeId = wp_insert_post( [
			'post_title'   => 'TestTimeframe',
			'post_type'=> \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
			'post_status' => 'publish'
		] );
		update_post_meta( $this->firstTimeframeId, 'type', \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID );
		update_post_meta( $this->firstTimeframeId, 'timeframe-repetition', 'w');
		update_post_meta( $this->firstTimeframeId, 'start-time','8:00 AM');
		update_post_meta( $this->firstTimeframeId, 'end-time', '12:00 PM');
		update_post_meta( $this->firstTimeframeId, 'timeframe-max-days', '3');
		update_post_meta( $this->firstTimeframeId, 'location-id', $this->locationId);
		update_post_meta( $this->firstTimeframeId, 'item-id', $this->itemId);
		update_post_meta( $this->firstTimeframeId, 'grid','0');
		update_post_meta( $this->firstTimeframeId, 'repetition-start', self::REPETITION_START);
		update_post_meta( $this->firstTimeframeId, 'repetition-end', self::REPETITION_END);
		update_post_meta( $this->firstTimeframeId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);
		$this->timeframeIds[] = $this->firstTimeframeId;
	}

	/**
	 * Timeframe without end date.
	 */
	protected function createBookableTimeFrameWithoutEnddate() {
		// Create Timeframe
		$this->secondTimeframeId = wp_insert_post( [
			'post_title'   => 'TestTimeframe',
			'post_type'=> \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
			'post_status' => 'publish'
		] );
		update_post_meta( $this->secondTimeframeId, 'type', \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID );
		update_post_meta( $this->secondTimeframeId, 'timeframe-repetition', 'w');
		update_post_meta( $this->secondTimeframeId, 'start-time','13:00 AM');
		update_post_meta( $this->secondTimeframeId, 'end-time', '17:00 PM');
		update_post_meta( $this->secondTimeframeId, 'timeframe-max-days', '3');
		update_post_meta( $this->secondTimeframeId, 'location-id', $this->locationId);
		update_post_meta( $this->secondTimeframeId, 'item-id', $this->itemId);
		update_post_meta( $this->secondTimeframeId, 'grid','0');
		update_post_meta( $this->secondTimeframeId, 'repetition-start', self::REPETITION_START);
		update_post_meta( $this->secondTimeframeId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);
		$this->timeframeIds[] = $this->secondTimeframeId;
	}

	protected function setUpBookingCodesTable() {
		global $wpdb;
		$table_name      = $wpdb->prefix . BookingCodes::$tablename;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code) 
        ) $charset_collate;";

		$wpdb->query($sql);
	}

	protected function setUp() {
		parent::setUp();

		$this->setUpBookingCodesTable();

		// Create location
		$this->locationId = wp_insert_post( [
			'post_title'   => 'TestLocation',
			'post_type' => Location::$postType,
			'post_status' => 'publish'
		] );

		// Create Item
		$this->itemId = wp_insert_post( [
			'post_title'   => 'TestItem',
			'post_type'=> Item::$postType,
			'post_status' => 'publish'
		] );
	}

	protected function tearDownAllBookings() {
		foreach ($this->bookingIds as $id) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllTimeframes() {
		foreach ($this->timeframeIds as $id) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$wpdb->query( $sql );
	}

	protected function tearDown() {
		parent::tearDown();
		wp_delete_post( $this->itemId, true );
		wp_delete_post( $this->locationId, true );

		$this->tearDownAllTimeframes();
		$this->tearDownAllBookings();
		$this->tearDownBookingCodesTable();
	}

}
