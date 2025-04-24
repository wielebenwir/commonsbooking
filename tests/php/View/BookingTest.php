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
}
