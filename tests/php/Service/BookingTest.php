<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class BookingTest extends CustomPostTypeTest {
	public function testCleanupBookings() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed'
		);
		//first, we check if the cleanup will delete our freshly created unconfirmed booking (it should not)
		Booking::cleanupBookings();
		$this->assertNotNull( get_post( $bookingId ) );

		//we make the post 11 minutes old, so that the cleanup function will delete it (the cleanup function only deletes bookings older than 10 minutes)
		wp_update_post( [
			'ID'        => $bookingId,
			'post_date' => date( 'Y-m-d H:i:s', strtotime( '-11 minutes' ) )
		] );

		//now we run the cleanup function again
		Booking::cleanupBookings();

		//and check if the post is still there
		$this->assertNull( get_post( $bookingId ) );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}
}
