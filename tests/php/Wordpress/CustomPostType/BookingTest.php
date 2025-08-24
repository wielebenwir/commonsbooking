<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Service\Booking as BookingAlias;
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
		$bookingId = BookingAlias::handleBookingRequest(
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
		$newBookingId       = BookingAlias::handleBookingRequest(
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
		$this->bookingIds[] = $newBookingId;

		// the id should be the same
		$this->assertEquals( $bookingId, $newBookingId );
		// we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );

		// Case 3: We now try to cancel our booking a little bit later. The booking should be cancelled.
		$date->modify( '+ 5 hours' );
		ClockMock::freeze( $date );
		$canceledId         = BookingAlias::handleBookingRequest(
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
		$bookingId          = BookingAlias::handleBookingRequest(
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
		BookingAlias::handleBookingRequest(
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
		$bookingId = BookingAlias::handleBookingRequest(
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
		$newBookingId       = BookingAlias::handleBookingRequest(
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
		$this->bookingIds[] = $newBookingId;

		// the id should be the same
		$this->assertEquals( $bookingId, $newBookingId );
		// we create a new model, just to be sure
		$bookingModel = new \CommonsBooking\Model\Booking( $bookingId );
		$this->assertTrue( $bookingModel->isConfirmed() );
		$this->assertFalse( $bookingModel->isUnconfirmed() );
		// two of those days are counted as overbooked, first day is still counted to maximum quota
		$this->assertEquals( 2, $bookingModel->getOverbookedDays() );
	}

	public function testBookingWithoutLoc() {
		// Case 1: We try to create a booking without a defined location
		$this->expectException( \CommonsBooking\Exception\BookingDeniedException::class );
		$this->expectExceptionMessage( 'Location does not exist. ()' );
		$booking = BookingAlias::handleBookingRequest(
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
		$booking = BookingAlias::handleBookingRequest(
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
		$booking = BookingAlias::handleBookingRequest(
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
		$booking = BookingAlias::handleBookingRequest(
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
		$booking = BookingAlias::handleBookingRequest(
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
		$bookingId = BookingAlias::handleBookingRequest(
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
		$sameBookingId      = BookingAlias::handleBookingRequest(
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
		$bookingId = BookingAlias::handleBookingRequest(
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
		$confirmedId = BookingAlias::handleBookingRequest(
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
	 * This will check if the bookings can be exported through the WordPress personal data export tool
	 * @return void
	 */
	public function testExportUserBookingsByEmail() {
		$booking    = new \CommonsBooking\Model\Booking(
			$this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		$fullExport = Booking::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $fullExport );
		$this->assertCount( 1, $fullExport['data'] );
		$this->assertTrue( $fullExport['done'] );
		$data = $fullExport['data'][0]['data'];
		$this->assertEquals( $booking->pickupDatetime(), $data[0]['value'] );

		// get empty export when e-mail is unknown
		$emptyExport = Booking::exportUserBookingsByEmail( 'doi@knowy.ou' );
		$this->assertIsArray( $emptyExport );
		$this->assertCount( 0, $emptyExport['data'] );
		$this->assertTrue( $emptyExport['done'] );

		// make sure, that the export does not contain any other bookings (like bookings that are not the user's own)
		$this->createAdministrator();
		$emptyExport = Booking::exportUserBookingsByEmail( get_user_by( 'ID', $this->adminUserID )->user_email );
		$this->assertIsArray( $emptyExport );
		$this->assertCount( 0, $emptyExport['data'] );
		$this->assertTrue( $emptyExport['done'] );

		// now, we test the proper export of multiple bookings with pagination
		$bookingIds = [ $booking->ID ];
		for ( $i = 0; $i < 20; $i++ ) {
			$bookingIds[] = $this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( '+' . ( $i + 10 ) . ' day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 11 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			);
		}
		$partialExport = Booking::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $partialExport );
		$this->assertCount( 10, $partialExport['data'] );
		$this->assertFalse( $partialExport['done'] );
		$otherPartialExport = Booking::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 2 );
		$this->assertIsArray( $otherPartialExport );
		$this->assertCount( 10, $otherPartialExport['data'] );
		$this->assertFalse( $otherPartialExport['done'] );
		$lastPartialExport = Booking::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 3 );
		$this->assertIsArray( $lastPartialExport );
		$this->assertCount( 1, $lastPartialExport['data'] );
		$this->assertTrue( $lastPartialExport['done'] );
	}

	public function testRemoveUserBookingsByEmail() {
		$booking   = new \CommonsBooking\Model\Booking(
			$this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		$deleteAll = Booking::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $deleteAll );
		$this->assertTrue( $deleteAll['items_removed'] );
		$this->assertFalse( $deleteAll['items_retained'] );
		$this->assertEmpty( $deleteAll['messages'] );
		$this->assertTrue( $deleteAll['done'] );

		// now we create a bunch of bookings and delete them in chunks
		$bookingIds = [];
		for ( $i = 0; $i < 21; $i++ ) {
			$bookingIds[] = $this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( '+' . ( $i + 10 ) . ' day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 11 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			);
		}
		// quickly test if the bookings are there
		$this->assertCount( 21, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );

		$deleteFirstPage = Booking::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $deleteFirstPage );
		$this->assertTrue( $deleteFirstPage['items_removed'] );
		$this->assertFalse( $deleteFirstPage['items_retained'] );
		$this->assertEmpty( $deleteFirstPage['messages'] );
		$this->assertFalse( $deleteFirstPage['done'] );
		$this->assertCount( 11, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );

		$deleteSecondPage = Booking::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 2 );
		$this->assertCount( 1, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );
		$this->assertIsArray( $deleteSecondPage );
		$this->assertTrue( $deleteSecondPage['items_removed'] );
		$this->assertFalse( $deleteSecondPage['items_retained'] );
		$this->assertEmpty( $deleteSecondPage['messages'] );
		$this->assertFalse( $deleteSecondPage['done'] );

		$deleteThirdPage = Booking::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 3 );
		$this->assertIsArray( $deleteThirdPage );
		$this->assertTrue( $deleteThirdPage['items_removed'] );
		$this->assertFalse( $deleteThirdPage['items_retained'] );
		$this->assertEmpty( $deleteThirdPage['messages'] );
		$this->assertTrue( $deleteThirdPage['done'] );
		$this->assertEmpty( \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );
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
