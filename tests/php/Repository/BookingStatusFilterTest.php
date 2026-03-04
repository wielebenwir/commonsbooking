<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

/**
 * Verifies that every public Repository\Booking retrieval method:
 *  (a) excludes 'cb-outdated' bookings by default, and
 *  (b) can include them when the caller explicitly passes 'cb-outdated'
 *      in the $postStatus parameter.
 *
 * Tests (b) are RED before the $postStatus parameters are added to the
 * hardcoded methods (getEndingBookingsByDate, getBeginningBookingsByDate,
 * getByDate) and GREEN afterwards.
 */
class BookingStatusFilterTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}

	// -----------------------------------------------------------------------
	// getByTimerange — already has $postStatus param
	// -----------------------------------------------------------------------

	public function testGetByTimerange_excludesCbOutdatedByDefault() {
		$start = strtotime( '-3 days' );
		$end   = strtotime( '-2 days' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$start,
			$end,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getByTimerange( $start, $end, $this->locationId, $this->itemId );
		$ids      = array_map( fn( $b ) => $b->ID, $bookings );

		$this->assertNotContains( $bookingId, $ids );
	}

	public function testGetByTimerange_includesCbOutdatedWhenExplicitlyRequested() {
		$start = strtotime( '-3 days' );
		$end   = strtotime( '-2 days' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$start,
			$end,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getByTimerange(
			$start,
			$end,
			$this->locationId,
			$this->itemId,
			[],
			[ 'confirmed', 'unconfirmed', 'cb-outdated' ]
		);
		$ids = array_map( fn( $b ) => $b->ID, $bookings );

		$this->assertContains( $bookingId, $ids );
	}

	// -----------------------------------------------------------------------
	// getEndingBookingsByDate — hardcoded status before fix
	// -----------------------------------------------------------------------

	public function testGetEndingBookingsByDate_excludesCbOutdatedByDefault() {
		$endTs = strtotime( 'midnight' );

		$this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days' ),
			$endTs,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getEndingBookingsByDate( $endTs );
		foreach ( $bookings as $booking ) {
			$this->assertNotEquals( 'cb-outdated', $booking->post_status );
		}
	}

	/**
	 * RED before adding $postStatus param to getEndingBookingsByDate().
	 * GREEN after.
	 */
	public function testGetEndingBookingsByDate_includesCbOutdatedWhenExplicitlyRequested() {
		$endTs = strtotime( 'midnight' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days' ),
			$endTs,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getEndingBookingsByDate(
			$endTs,
			[],
			[ 'confirmed', 'unconfirmed', 'cb-outdated' ]
		);
		$ids = array_map( fn( $b ) => $b->ID, $bookings );

		$this->assertContains( $bookingId, $ids );
	}

	// -----------------------------------------------------------------------
	// getBeginningBookingsByDate — hardcoded status before fix
	// -----------------------------------------------------------------------

	public function testGetBeginningBookingsByDate_excludesCbOutdatedByDefault() {
		$startTs = strtotime( 'midnight' );

		$this->createBooking(
			$this->locationId,
			$this->itemId,
			$startTs,
			strtotime( '+2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getBeginningBookingsByDate( $startTs );
		foreach ( $bookings as $booking ) {
			$this->assertNotEquals( 'cb-outdated', $booking->post_status );
		}
	}

	/**
	 * RED before adding $postStatus param to getBeginningBookingsByDate().
	 * GREEN after.
	 */
	public function testGetBeginningBookingsByDate_includesCbOutdatedWhenExplicitlyRequested() {
		$startTs = strtotime( 'midnight' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$startTs,
			strtotime( '+2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getBeginningBookingsByDate(
			$startTs,
			[],
			[ 'confirmed', 'cb-outdated' ]
		);
		$ids = array_map( fn( $b ) => $b->ID, $bookings );

		$this->assertContains( $bookingId, $ids );
	}

	// -----------------------------------------------------------------------
	// getByDate — hardcoded status before fix
	// -----------------------------------------------------------------------

	public function testGetByDate_excludesCbOutdatedByDefault() {
		$start = strtotime( '-3 days' );
		$end   = strtotime( '-2 days' );

		$this->createBooking(
			$this->locationId,
			$this->itemId,
			$start,
			$end,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$result = Booking::getByDate( $start, $end, $this->locationId, $this->itemId );

		$this->assertNull( $result );
	}

	/**
	 * RED before adding $postStatus param to getByDate().
	 * GREEN after.
	 */
	public function testGetByDate_includesCbOutdatedWhenExplicitlyRequested() {
		$start = strtotime( '-3 days' );
		$end   = strtotime( '-2 days' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$start,
			$end,
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$result = Booking::getByDate(
			$start,
			$end,
			$this->locationId,
			$this->itemId,
			[ 'confirmed', 'unconfirmed', 'cb-outdated' ]
		);

		$this->assertNotNull( $result );
		$this->assertEquals( $bookingId, $result->ID );
	}

	// -----------------------------------------------------------------------
	// getForUser — already has $postStatus param
	// -----------------------------------------------------------------------

	public function testGetForUser_excludesCbOutdatedByDefault() {
		$user = get_user_by( 'id', self::USER_ID );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::getForUser( $user, false );
		$ids      = array_map(
			fn( $b ) => is_object( $b ) ? $b->ID : $b,
			$bookings
		);

		$this->assertNotContains( $bookingId, $ids );
	}

	// -----------------------------------------------------------------------
	// get() (Booking repo) — already has $postStatus param
	// -----------------------------------------------------------------------

	public function testGet_excludesCbOutdatedByDefault() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		$bookings = Booking::get();
		$ids      = array_map(
			fn( $b ) => is_object( $b ) ? $b->ID : $b,
			$bookings
		);

		$this->assertNotContains( $bookingId, $ids );
	}
}
