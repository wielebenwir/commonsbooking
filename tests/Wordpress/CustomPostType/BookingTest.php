<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingTest extends CustomPostTypeTest
{

	private \CommonsBooking\Model\Timeframe $timeframeModel;

	/**
	 * This tests the booking form request method.
	 * These are the regular scenarios where nothing should go wrong.
	 * @return void
	 */
	public function testHandleBookingRequestDefautl() {
		wp_set_current_user($this->normalUserID);
		//Case 1: We create an unconfirmed booking for a bookable timeframe. The unconfirmed booking should be created
		$bookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
		$this->assertIsInt( $bookingId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );

		$postName = $bookingModel->post_name;

		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$this->assertFalse( $bookingModel->isConfirmed() );

		//Case 2: We now confirm the booking. The booking should be confirmed
		$newBookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'confirmed',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			null
		);

		//the id should be the same
		$this->assertEquals( $bookingId, $newBookingId );
		//we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		//Case 3: We now try to cancel our booking. The booking should be cancelled.
		$canceledId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'canceled',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			null
		);
		$this->assertEquals( $bookingId, $canceledId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isCancelled() );
		$this->assertFalse( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		//Case 4: We create an unconfirmed booking and then cancel the booking. The booking should be canceled
		$bookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
		$this->assertIsInt( $bookingId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$postName = $bookingModel->post_name;
		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$canceledUnconfirmedId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'canceled',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			null
		);
		$this->assertEquals( $bookingId, $canceledUnconfirmedId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isCancelled() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );
	}

	public function testHandleBookingRequestExceptions(){
		

	}
	protected function setUp(): void {
		parent::setUp();
		$this->timeframeModel = new \CommonsBooking\Model\Timeframe(
			$this->createBookableTimeFrameIncludingCurrentDay()
		);
		$this->createSubscriber();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
