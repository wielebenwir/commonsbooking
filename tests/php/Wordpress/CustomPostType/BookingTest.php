<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use SlopeIt\ClockMock\ClockMock;

/**
 * This class tests the form request for the frontend booking process
 *
 * TODO: Test the case, where one user creates an unconfirmed booking and an admin creates a booking for the same item, location and timeframe.
 *       Right now, it will show the booking of the user to the admin. Instead, the admin should be notified that there is already a booking for this timeframe.
 */
class BookingTest extends CustomPostTypeTest {


	private \CommonsBooking\Model\Timeframe $timeframeModel;

	/**
	 * This tests the booking form request method.
	 * These are the regular scenarios where nothing should go wrong.
	 * @return void
	 */
	public function testHandleBookingRequest_Default() {
		$date = new \DateTime( self::CURRENT_DATE );
		$date->modify( '-1 day' );
		ClockMock::freeze( $date );
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
		// add this to the array so it can be destroyed later
		$this->bookingIds[] = $bookingId;

		$this->assertIsInt( $bookingId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );

		$postName = $bookingModel->post_name;

		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$this->assertFalse( $bookingModel->isConfirmed() );

		// Case 2: We now confirm the booking. The booking should be confirmed
		$confirmedBookingId = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $confirmedBookingId;

		// the id should be the same
		$this->assertEquals( $bookingId, $confirmedBookingId );
		// we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		// Case 3: We now try to cancel our booking a little bit later. The booking should be cancelled.
		$date->modify( '+ 5 hours' );
		ClockMock::freeze( $date );
		$canceledId         = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $canceledId;

		$this->assertEquals( $bookingId, $canceledId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isCancelled() );
		$this->assertFalse( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		// check, if the cancel time is correct
		$cancelDate = $bookingModel->getCancellationDateDateTime();
		$this->assertEquals( $date->format( 'Y-m-d H:i:s' ), $cancelDate->format( 'Y-m-d H:i:s' ) );
	}

	public function testHandleBookingRequest_deleteUnconfirmed() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// We create an unconfirmed booking and then cancel the booking. The booking should be canceled
		$bookingId          = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( '+1 day' ),
			strtotime( '+2 days' ),
			null,
			null
		);
		$this->bookingIds[] = $bookingId;

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
			strtotime( '+1 day' ),
			strtotime( '+2 days' ),
			$postName,
			null
		);
	}

	public function testHandleBookingRequest_Overbooking() {
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range', 'on' );
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', '1' );
		$date = new \DateTime( self::CURRENT_DATE );
		$date->modify( '-1 day' );
		ClockMock::freeze( $date );
		// 3 Days are overbooked, that means that the Litepicker had 3 locked / holidays in range
		$bookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+5 day', strtotime( self::CURRENT_DATE ) ),
			null,
			null,
			3
		);
		// add this to the array so it can be destroyed later
		$this->bookingIds[] = $bookingId;

		$this->assertIsInt( $bookingId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );

		$postName = $bookingModel->post_name;

		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$this->assertFalse( $bookingModel->isConfirmed() );

		// The overbooked days are not present anymore when confirming the booking cause they are only calculated on the Litepicker screen
		$confirmedBookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'confirmed',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+5 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			null
		);
		$this->bookingIds[] = $confirmedBookingId;

		// the id should be the same
		$this->assertEquals( $bookingId, $confirmedBookingId );
		// we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );
		// two of those days are counted as overbooked, first day is still counted to maximum quota
		$this->assertEquals( 2, $bookingModel->getOverbookedDays() );
	}

	/**
	 * Makes sure, that bookings that are created always have to be unconfirmed first and then confirmed.
	 * It should not be possible to create a confirmed or canceled booking immediately.
	 * Fixes #2295, where impatient users who would click cancel multiple times would create new bookings.
	 * @return void
	 */
	public function testHandleBookingRequest_noDirectCreation() {
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$bookingId          = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID
		);
		$this->bookingIds[] = $bookingId;
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'confirmed',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			get_post( $bookingId )->post_name,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID
		);

		// cancel once
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'canceled',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			get_post( $bookingId )->post_name,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID
		);

		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		// cancel twice, should throw exception
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'canceled',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			get_post( $bookingId )->post_name,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID
		);
	}

	/**
	 * Regression test for #1518
	 * Users should only have one unconfirmed booking at a time.
	 * This is because checks for booking validity only happen when unconfirmed bookings are created.
	 * Multiple unconfirmed bookings can lead to circumvention of booking restrictions in the form of booking rules.
	 * @return void
	 */
	public function testHandleBookingRequest_onlyOneUnconfirmedBooking() {
		$bookingId          = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $bookingId;

		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessageMatches( '/You already have an unconfirmed booking/' );
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( '+3 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
	}

	/**
	 * edge case for #1518
	 * check, that unconfirmed bookings of other users are not counted against the current user.
	 * This might be the case for admins or cb_manager.
	 * @return void
	 */
	public function testHandleBookingRequest_onlyOneUnconfirmedBooking_withAdmin() {
		$this->createSubscriber();
		wp_set_current_user( $this->subscriberId );
		$bookingId          = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $bookingId;

		$this->createAdministrator();
		wp_set_current_user( $this->adminUserID );
		// if this test fails, an exception would be thrown
		$bookingTwoId       = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( '+3 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
			null,
			null
		);
		$this->bookingIds[] = $bookingTwoId;
		$this->assertNotNull( $bookingTwoId );
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
		// add this to the array so it can be destroyed later
		$this->bookingIds[] = $bookingId;

		$this->assertIsInt( $bookingId );
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isUnconfirmed() );
		$sameBookingId      = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $sameBookingId;

		// we now make sure that we got the same booking back
		$this->assertEquals( $bookingId, $sameBookingId );
		$sameBookingModel = new \CommonsBooking\Model\Booking( $sameBookingId );
		$this->assertEquals( $bookingModel->post_name, $sameBookingModel->post_name );
	}

	/**
	 * This test is meant to test a bunch of behaviour that can occur
	 * when a booking is created as unconfirmed first, then deleted by the cronjob and then either confirmed or canceled.
	 * Issue: #1584
	 *
	 * @return void
	 */
	public function testHandleBookingRequest_deleted_confirm() {
		$bookingId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			null,
			'6'
		);
		$postName  = get_post( $bookingId )->post_name;

		// delete the post just like the cronjob would
		wp_delete_post( $bookingId, true );

		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Your reservation has expired, please try to book again' );

		// now we try to confirm the booking
		$confirmedId = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'confirmed',
			$bookingId,
			null,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$postName,
			'6'
		);
	}

	/**
	 * Regression test for #2217
	 * When a booking is confirmed and another booking is created in the exact same timeframe afterwards,
	 * the previously confirmed booking was set to unconfirmed again. This test is in place to ensure that
	 * this does not happen again.
	 *
	 * @return void
	 */
	public function testHandleBookingRequest_noRecreation() {
		$date = new \DateTime( self::CURRENT_DATE );
		$date->modify( '-1 day' );
		ClockMock::freeze( $date );
		// create regular booking through unconfirmed -> confirmed route
		$bookingId          = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $bookingId;

		$bookingModel       = new \CommonsBooking\Model\Booking( $bookingId );
		$postName           = $bookingModel->post_name;
		$confirmedBookingId = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $confirmedBookingId;

		// attempt to recreate the booking, should keep status as "confirmed" because it was not explicitly cancelled
		$bookingId          = Booking::handleBookingRequest(
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
		$this->bookingIds[] = $bookingId;

		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
	}

	/** User cannot book a slot already confirmed by another user (issue #1864) */
	public function testUserCannotBookSlotConfirmedByAnotherUser() {
		$date = new \DateTime( self::CURRENT_DATE );
		$date->modify( '-1 day' );
		ClockMock::freeze( $date );

		$repetitionStart = strtotime( self::CURRENT_DATE );
		$repetitionEnd   = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );

		$user1BookingId     = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			$repetitionStart,
			$repetitionEnd,
			null,
			null
		);
		$this->bookingIds[] = $user1BookingId;
		$postName           = get_post( $user1BookingId )->post_name;
		Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'confirmed',
			$user1BookingId,
			null,
			$repetitionStart,
			$repetitionEnd,
			$postName,
			null
		);

		wp_set_current_user( $this->createSecondSubscriber() );

		$thrown = false;
		try {
			$result             = Booking::handleBookingRequest(
				$this->itemId,
				$this->locationId,
				'unconfirmed',
				null,
				null,
				$repetitionStart,
				$repetitionEnd,
				null,
				null
			);
			$this->bookingIds[] = $result;
		} catch ( \CommonsBooking\Exception\BookingDeniedException $e ) {
			$thrown = true;
		}

		$this->assertTrue( $thrown, 'Expected BookingDeniedException when another user tries to book the same confirmed slot' );
		$user1BookingAfter = new \CommonsBooking\Model\Booking( $user1BookingId );
		$this->assertTrue( $user1BookingAfter->isConfirmed(), 'Expected first booking to still be confirmed' );
	}

	/** User cannot book a slot already held unconfirmed by another user (issue #1864) */
	public function testDifferentUserCannotBookSlotHeldUnconfirmedByAnotherUser() {
		$date = new \DateTime( self::CURRENT_DATE );
		$date->modify( '-1 day' );
		ClockMock::freeze( $date );

		$repetitionStart = strtotime( self::CURRENT_DATE );
		$repetitionEnd   = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );

		$user1BookingId     = Booking::handleBookingRequest(
			$this->itemId,
			$this->locationId,
			'unconfirmed',
			null,
			null,
			$repetitionStart,
			$repetitionEnd,
			null,
			null
		);
		$this->bookingIds[] = $user1BookingId;

		wp_set_current_user( $this->createSecondSubscriber() );

		$thrown = false;
		try {
			$result             = Booking::handleBookingRequest(
				$this->itemId,
				$this->locationId,
				'unconfirmed',
				null,
				null,
				$repetitionStart,
				$repetitionEnd,
				null,
				null
			);
			$this->bookingIds[] = $result;
		} catch ( \CommonsBooking\Exception\BookingDeniedException $e ) {
			$thrown = true;
		}

		$this->assertTrue( $thrown, 'Expected BookingDeniedException when another user tries to book the slot held unconfirmed' );
	}

	/**
	 * Second subscriber for multi-user booking tests.
	 * @return int
	 */
	private function createSecondSubscriber(): int {
		$wp_user = get_user_by( 'email', 'b@b.de' );
		if ( ! $wp_user ) {
			return wp_create_user( 'seconduser', 'second', 'b@b.de' );
		}
		return $wp_user->ID;
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
