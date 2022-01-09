<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Booking;
use PHPUnit\Framework\TestCase;

final class BookingTest extends CustomPostTypeTest {



	protected function setUp() {
		parent::setUp();
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			time() - 86400,
			time() + 86400
		);
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetBookingListData() {
		wp_set_current_user( self::USER_ID );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );
	}

}
