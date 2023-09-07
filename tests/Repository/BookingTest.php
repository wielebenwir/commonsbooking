<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use Exception;
use WP_Post;

class BookingTest extends CustomPostTypeTest {

	private $timeframeOne;

	private $testItem;

	private $testLocation;

	private $restriction;

	private $restriction2;

	private $testBooking;

	/**
	 * Test that we only get the booking, ending today.
	 * @return void
	 * @throws Exception
	 */
	public function testGetEndingBookingsByDate() {
		$endingBookingsToday = Booking::getEndingBookingsByDate( strtotime( self::CURRENT_DATE ) );
		$this->assertCount( 1, $endingBookingsToday );
	}

	/**
	 * Test that we only get the booking, starting today.
	 * @return void
	 * @throws Exception
	 */
	public function testGetBeginningBookingsByDate() {
		$beginningBookingsToday = Booking::getBeginningBookingsByDate( strtotime( self::CURRENT_DATE ) );
		$this->assertCount( 1, $beginningBookingsToday );
	}

	/**
	 * Test that we get all bookings in date range.
	 * @return void
	 * @throws Exception
	 */
	public function testGetByTimerange() {
		$booking = Booking::getByDate(
			get_post_meta( $this->timeframeOne, Timeframe::REPETITION_START, true ),
			get_post_meta( $this->timeframeOne, Timeframe::REPETITION_END, true ),
			$this->locationId,
			$this->itemId
		);

		$this->assertTrue( $booking instanceof \CommonsBooking\Model\Booking );
		$this->assertTrue( $booking->getPost()->ID == $this->timeframeOne );

		$booking = Booking::getByDate(
			strtotime( 'midnight' ),
			time(),
			$this->locationId,
			$this->itemId
		);
		$this->assertNull( $booking );
	}

	/**
	 * Test if we get bookings for current user.
	 * @return void
	 * @throws Exception
	 */
	public function testGetForCurrentUser() {
		// Without current user we shouldn't get bookings.
		$bookings = Booking::getForCurrentUser();
		$this->assertCount( 0, $bookings );

		// Set current user ID
		wp_set_current_user( self::USER_ID );

		// Test that we get bookings now
		$bookings = Booking::getForCurrentUser();
		$this->assertCount( count( $this->bookingIds ), $bookings );
		$this->assertIsArray( $bookings );

		// Test if bookings are of type WP_Post
		$this->assertContainsOnlyInstancesOf( WP_Post::class, $bookings );

		// Test if bookings are of type Booking
		$bookings = Booking::getForCurrentUser( true );
		$this->assertContainsOnlyInstancesOf( \CommonsBooking\Model\Booking::class, $bookings );

		// Test if date param works
		$bookings = Booking::getForCurrentUser(
			true,
			strtotime( 'tomorrow midnight', strtotime( self::CURRENT_DATE ) )
		);
		$this->assertCount( 2, $bookings );
	}

	public function testCentralBookingsGetFunction() {
		// Test without params
		$this->assertCount( count( $this->bookingIds ), Booking::get() );
		$this->assertContainsOnlyInstancesOf( WP_Post::class, Booking::get() );

		// Return as model
		$this->assertContainsOnlyInstancesOf(
			\CommonsBooking\Model\Booking::class,
			Booking::get( [], [], null, true )
		);

		// Test with location
		$this->assertCount( 1, Booking::get( [ $this->testLocation ] ) );
		$this->assertCount( 1, Booking::get( [], [ $this->testItem ] ) );
		$this->assertCount( 1, Booking::get( [ $this->testLocation ], [ $this->testItem ] ) );

		// Test date param and make sure there is no booking in 200 days
		$this->assertCount( 0,
			Booking::get(
				[],
				[],
				date( 'Y-m-d', strtotime( '+200 days', strtotime( self::CURRENT_DATE ) ) )
			)
		);

		// Test date param and make sure there we find bookings for today
		$this->assertCount( 2,
			Booking::get(
				[],
				[],
				date( 'Y-m-d', strtotime( self::CURRENT_DATE ) )
			)
		);

		// Test minimum timestamp param and make sure we find bookings tomorrow
		$this->assertCount( 2,
			Booking::get(
				[],
				[],
				null,
				false,
				strtotime('+1 days', strtotime(self::CURRENT_DATE) )
			)
		);

		// Test minimum timestamp param; there shouldn't be bookings in 200 days
		$this->assertCount( 0,
			Booking::get(
				[],
				[],
				null,
				false,
				strtotime('+200 days', strtotime(self::CURRENT_DATE) )
			)
		);
	}

	/**
	 * Tests regarding search by restriction.
	 * @return void
	 * @throws Exception
	 */
	public function testGetByRestriction() {
		// Positive test, should find exactly 1 booking
		$restriction = new \CommonsBooking\Model\Restriction($this->restriction);
		$this->assertCount(1, Booking::getByRestriction($restriction));

		// Negative test, restriction is in future of all bookings, shouldn't find one
		$restriction = new \CommonsBooking\Model\Restriction($this->restriction2);
		$this->assertCount(0, Booking::getByRestriction($restriction));
	}

	protected function setUp() : void {
		parent::setUp();
		$this->timeframeOne = parent::createConfirmedBookingEndingToday();
		$this->timeframeTwo = parent::createConfirmedBookingStartingToday();
		$this->testItem     = parent::createItem( 'testitem', 'publish' );
		$this->testLocation = parent::createLocation( 'testlocation', 'publish' );

		$this->testBooking = $this->createBooking(
			$this->testLocation,
			$this->testItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);

		$this->restriction = self::createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			$this->testLocation,
			$this->testItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);

		$this->restriction2 = self::createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( '+50 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+52 days', strtotime( self::CURRENT_DATE ) )
		);
	}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
