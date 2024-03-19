<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Messages\LocationBookingReminderMessage;
use CommonsBooking\Messages\Message;
use CommonsBooking\Service\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class BookingTest extends CustomPostTypeTest
{
	private int $bookingId;
    public function testCleanupBookings()
    {
		//first, we check if the cleanup will delete our freshly created unconfirmed booking (it should not)
	    Booking::cleanupBookings();
		$this->assertNotNull(get_post($this->bookingId));

	    //we make the post 11 minutes old, so that the cleanup function will delete it (the cleanup function only deletes bookings older than 10 minutes)
	    wp_update_post([
		    'ID' => $this->bookingId,
		    'post_date' => date('Y-m-d H:i:s', strtotime('-11 minutes'))
	    ]);

		//now we run the cleanup function again
	    Booking::cleanupBookings();

	    //and check if the post is still there
	    $this->assertNull(get_post($this->bookingId));
    }

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function testSendMessagesForDay()
	{
		//create confirmed booking starting today
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);
		// Mock the Message class and its methods
		$mockMessage = \Mockery::mock('overload:' . Message::class);
		$mockMessage->shouldReceive('__construct')->times(1)->with($bookingId, \Mockery::any());
		$mockMessage->shouldReceive('triggerMail')->once();
		$mockMessage->shouldReceive('getAction')->andReturn('test');

		Booking::sendMessagesForDay(strtotime( self::CURRENT_DATE ), true, $mockMessage);
		$mockMessage->shouldHaveReceived('triggerMail');

		//we need this so that PHPUnit won't complain, assertions are made in the mock
		$this->assertTrue(true);
	}
	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed'
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}
}
