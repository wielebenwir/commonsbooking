<?php

namespace View;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\View\Booking;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase {

	private $bookingId;

	const USER_ID = 1;

	const BOOKING_ID = 1;

	/**
	 * Timeframe with enddate.
	 */
	protected function createBooking() {
		// Create Timeframe
		$this->bookingId = wp_insert_post( [
			'post_title'  => 'TestTimeframe',
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
			'post_status' => 'confirmed',
			'post_author' => self::USER_ID
		] );

		update_post_meta( $this->bookingId, 'type', \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID );
		update_post_meta( $this->bookingId, 'timeframe-repetition', 'w' );
		update_post_meta( $this->bookingId, 'start-time', '8:00 AM' );
		update_post_meta( $this->bookingId, 'end-time', '12:00 PM' );
		update_post_meta( $this->bookingId, 'timeframe-max-days', '3' );
		update_post_meta( $this->bookingId, 'location-id', self::BOOKING_ID );
		update_post_meta( $this->bookingId, 'item-id', self::BOOKING_ID );
		update_post_meta( $this->bookingId, 'grid', '0' );
		update_post_meta( $this->bookingId, 'repetition-start', time() - 86400 );
		update_post_meta( $this->bookingId, 'repetition-end', time() + 86400 );
		update_post_meta( $this->bookingId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);
	}

	protected function setUp() {
		$this->createBooking();
		$this->setUpBookingCodesTable();
	}

	public function testGetBookingListData() {
		wp_set_current_user( self::USER_ID );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );
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
		wp_delete_post( $this->bookingId, true );

		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$wpdb->query( $sql );
	}
}
