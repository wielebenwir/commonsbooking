<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Booking;

final class BookingTest extends CustomPostTypeTest {


	protected function setUp(): void {
		parent::setUp();
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			time() - 86400,
			time() + 86400
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetBookingListData() {
		wp_set_current_user( self::USER_ID );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );

		// check for #1802, delete location. Booking list should still generate
		wp_delete_post( $this->locationId, true );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );
	}

	public function testGetBookingListData_withNullFilter() {
		wp_set_current_user( self::USER_ID );

		$nullCallback = function ( $rowData, $bookingObject ) {
			return null;
		};

		add_filter( 'commonsbooking_booking_filter', $nullCallback, 10, 2 );

		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] === 0 );

		remove_filter( 'commonsbooking_booking_filter', $nullCallback );
	}

	public function testGetBookingListData_withSimpleFilter() {
		wp_set_current_user( self::USER_ID );

		$simpleFilter = function ( $rowData, $bookingObject ) {
			if ( in_array( $rowData['postID'], $this->bookingIds, true ) ) {
				return $rowData;
			} else {
				return null;
			}
		};

		add_filter( 'commonsbooking_booking_filter', $simpleFilter, 10, 2 );

		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] === 1 );

		remove_filter( 'commonsbooking_booking_filter', $simpleFilter );
	}

	/*
	TODO implement more complex test (to test keys of rowData as input/filter and as output via getBookingListData
	public function testGetBookingListData_withComplexRowDataFilter() {

	}
	*/
}
