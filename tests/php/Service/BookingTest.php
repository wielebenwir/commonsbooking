<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class BookingTest extends CustomPostTypeTest {
	public function testCleanupJobs() {
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
		Booking::cleanupJobs();
		$this->assertNotNull( get_post( $bookingId ) );

	    //let 11 minutes pass
	    $later = new \DateTime(self::CURRENT_DATE);
		$later->modify('+11 minutes');
		ClockMock::freeze($later);

		//now we run the cleanup function again
		Booking::cleanupJobs();

		//and check if the post is still there
		$this->assertNull( get_post( $bookingId));

		//our bookable Timeframe is only valid for two days, let's check the checkbox that it should be deleted after that
	    update_post_meta($this->firstTimeframeId, 'delete-expired-timeframe', 'on');
		//asert that it is not deleted before
	    Booking::cleanupJobs();
	    $this->assertNotNull(get_post($this->firstTimeframeId));
		$later->modify('+2 days');
		ClockMock::freeze($later);
		Booking::cleanupJobs();
		$this->assertNull(get_post($this->firstTimeframeId));
    }

	protected function setUp(): void {
		parent::setUp();
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}
}
