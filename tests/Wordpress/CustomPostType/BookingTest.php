<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking;

/**
 * This class tests the form request for the frontend booking process
 *
 * TODO: Test the case, where one user creates an unconfirmed booking and an admin creates a booking for the same item, location and timeframe.
 *       Right now, it will show the booking of the user to the admin. Instead, the admin should be notified that there is already a booking for this timeframe.
 */
class BookingTest extends CustomPostTypeTest
{

	private \CommonsBooking\Model\Timeframe $timeframeModel;

	/**
	 * This tests the booking form request method.
	 * These are the regular scenarios where nothing should go wrong.
	 * @return void
	 */
	public function testHandleBookingRequestDefautl() {
		// Case 1: We create an unconfirmed booking for a bookable timeframe. The unconfirmed booking should be created
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

		// Case 2: We now confirm the booking. The booking should be confirmed
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

		// the id should be the same
		$this->assertEquals( $bookingId, $newBookingId );
		// we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		// Case 3: We now try to cancel our booking. The booking should be cancelled.
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

		// Case 4: We create an unconfirmed booking and then cancel the booking. The booking should be canceled
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
		$postName     = $bookingModel->post_name;
		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Booking canceled.' );
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'delete_unconfirmed',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			null
		);
	}

	public function testBookingWithoutLoc() {
		// Case 1: We try to create a booking without a defined location
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Location does not exist. ()' );
		$booking = Booking::handleBookingRequest(
			$this->itemId,
			null,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
	}
	public function testBookingWithoutItem() {
		// Case 2: We try to create a booking without a defined item
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Item does not exist. ()' );
		$booking = Booking::handleBookingRequest(
			null,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
	}
	public function testBookingWithoutStart() {
		// Case 3: We try to create a booking without a defined start date
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Start- and/or end-date is missing.' );
		$booking = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			null,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
	}
	public function testBookingWithoutEnd() {
		// Case 4: We try to create a booking without a defined end date
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Start- and/or end-date is missing.' );
		$booking = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			null,
			null,
			null
		);
	}
	public function testBookingOverlapping() {
		// Case 5: Overlapping booking in the same timerange
		$this->createConfirmedBookingStartingToday();
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'There is already a booking in this time-range. This notice may also appear if there is an unconfirmed booking in the requested period. Unconfirmed bookings are deleted after about 10 minutes. Please try again in a few minutes.' );
		$booking = Booking::handleBookingRequest(
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
	}

	public function testReAccessUnconfirmed() {
		// this tests the case where the same user tries to access their unconfirmed booking again
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
		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$sameBookingId = Booking::handleBookingRequest(
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
		// we now make sure that we got the same booking back
		$this->assertEquals( $bookingId, $sameBookingId );
		$sameBookingModel = new \CommonsBooking\Model\Booking( $sameBookingId );
		$this->assertEquals( $bookingModel->post_name, $sameBookingModel->post_name );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->timeframeModel = new \CommonsBooking\Model\Timeframe(
			$this->createBookableTimeFrameIncludingCurrentDay()
		);
		$this->createSubscriber();
		wp_set_current_user( $this->subscriberId );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
