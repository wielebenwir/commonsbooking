<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use Exception;
use SlopeIt\ClockMock\ClockMock;
use WP_Post;

class BookingTest extends CustomPostTypeTest {

	private $confirmedBookingEndingToday;

	private $confirmedBookingStartingToday;

	private $testItem;

	private $testLocation;

	private $restriction;

	private $restriction2;

	protected $testBooking;

	private $testTimeframe;

	/**
	 * Test that we only get the booking, ending today.
	 * @return void
	 * @throws Exception
	 */
	public function testGetEndingBookingsByDate() {
		$endingBookingsToday = Booking::getEndingBookingsByDate( strtotime( self::CURRENT_DATE ) );
		$this->assertCount( 1, $endingBookingsToday );
		$this->assertEquals( $this->confirmedBookingEndingToday, $endingBookingsToday[0]->ID );

		$tomorrow               = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );
		$endingBookingsTomorrow = Booking::getEndingBookingsByDate( $tomorrow );
		$this->assertCount( 0, $endingBookingsTomorrow );

		$inTwoDays               = strtotime( '+2 days', strtotime( self::CURRENT_DATE ) );
		$endingBookingsInTwoDays = Booking::getEndingBookingsByDate( $inTwoDays );
		$this->assertCount( 2, $endingBookingsInTwoDays );
		$this->assertEqualsCanonicalizing(
			[ $this->testBooking, $this->confirmedBookingStartingToday ],
			array_map( fn( $b ) => $b->ID, $endingBookingsInTwoDays )
		);

		// create test booking that ended yesterday
		$yesterday               = strtotime( '-1 day', strtotime( self::CURRENT_DATE ) );
		$booking                 = $this->createBooking(
			$this->testLocation,
			$this->testItem,
			strtotime( '-2 days', strtotime( self::CURRENT_DATE ) ),
			$yesterday,
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);
		$endingBookingsYesterday = Booking::getEndingBookingsByDate( $yesterday );
		$this->assertCount( 1, $endingBookingsYesterday );
		$this->assertEquals( $booking, $endingBookingsYesterday[0]->ID );
	}

	/**
	 * Test that we only get the booking, starting today.
	 * @return void
	 * @throws Exception
	 */
	public function testGetBeginningBookingsByDate() {
		$beginningBookingsToday = Booking::getBeginningBookingsByDate( strtotime( self::CURRENT_DATE ) );
		$this->assertCount( 1, $beginningBookingsToday );
		$this->assertEquals( $this->confirmedBookingStartingToday, $beginningBookingsToday[0]->ID );

		$tomorrow                  = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );
		$beginningBookingsTomorrow = Booking::getBeginningBookingsByDate( $tomorrow );
		$this->assertCount( 1, $beginningBookingsTomorrow );
		$this->assertEquals( $this->testBooking, $beginningBookingsTomorrow[0]->ID );
	}

	/**
	 * Test that we get all bookings in date range.
	 * @return void
	 * @throws Exception
	 */
	public function testGetByDate() {
		$booking = Booking::getByDate(
			get_post_meta( $this->confirmedBookingEndingToday, Timeframe::REPETITION_START, true ),
			get_post_meta( $this->confirmedBookingEndingToday, Timeframe::REPETITION_END, true ),
			$this->locationId,
			$this->itemId
		);

		$this->assertTrue( $booking instanceof \CommonsBooking\Model\Booking );
		$this->assertTrue( $booking->getPost()->ID == $this->confirmedBookingEndingToday );

		$booking = Booking::getByDate(
			strtotime( 'midnight' ),
			time(),
			$this->locationId,
			$this->itemId
		);
		$this->assertNull( $booking );
	}
	public function testGetByTimerange() {
		$bookings = Booking::getByTimerange(
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 1, $bookings );
		$this->assertEquals( $this->confirmedBookingStartingToday, $bookings[0]->ID );

		// test with empty location / item value

		// Test for all items / locations
		$otherItem     = parent::createItem( 'otheritem', 'publish' );
		$otherLocation = parent::createLocation( 'otherlocation', 'publish' );
		$booking       = parent::createBooking(
			$otherLocation,
			$otherItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
		$result        = Booking::getByTimerange(
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
		$this->assertCount( 3, $result );
		$this->assertEqualsCanonicalizing(
			[ $this->confirmedBookingStartingToday, $this->testBooking, $booking ],
			array_map( fn( $b ) => $b->ID, $result )
		);

		$bookingIDs = Booking::getByTimerange(
			get_post_meta( $this->confirmedBookingEndingToday, Timeframe::REPETITION_START, true ),
			get_post_meta( $this->confirmedBookingEndingToday, Timeframe::REPETITION_END, true ),
			$this->locationId,
			$this->itemId
		);

		$this->assertCount( 2, $bookingIDs );
		$this->assertEqualsCanonicalizing(
			array_map(
				function ( $booking ) {
					return $booking->ID;
				},
				$bookingIDs
			),
			[
				$this->confirmedBookingEndingToday,
				$this->confirmedBookingStartingToday,
			]
		);

		$bookingIDs = Booking::getByTimerange(
			strtotime( 'midnight' ),
			time(),
			$this->locationId,
			$this->itemId
		);
		$this->assertEmpty( $bookingIDs );

		// make sure, that it works for a timeframe in between
		$nextMonthBooking = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+29 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+32 days', strtotime( self::CURRENT_DATE ) )
		);
		$bookingIDs       = Booking::getByTimerange(
			strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+31 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 1, $bookingIDs );
		$this->assertEquals( $nextMonthBooking, $bookingIDs[0]->ID );

		// outside of the timeframe beginning
		$bookingIDs = Booking::getByTimerange(
			strtotime( '+28 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 1, $bookingIDs );
		$this->assertEquals( $nextMonthBooking, $bookingIDs[0]->ID );

		// and outside of the timeframe end
		$bookingIDs = Booking::getByTimerange(
			strtotime( '+31 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+32 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 1, $bookingIDs );
		$this->assertEquals( $nextMonthBooking, $bookingIDs[0]->ID );

		// but not after
		$bookingIDs = Booking::getByTimerange(
			strtotime( '+33 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+34 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 0, $bookingIDs );

		// let's create a bunch of bookings and see if they show up
		$bookingIDs = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$bookingIDs[] = $this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+' . ( $i + 60 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 61 ) . ' days', strtotime( self::CURRENT_DATE ) )
			);
		}
		$bookings = Booking::getByTimerange(
			strtotime( '+60 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+70 days', strtotime( self::CURRENT_DATE ) ),
			$this->locationId,
			$this->itemId
		);
		$this->assertCount( 10, $bookings );
		$this->assertEqualsCanonicalizing(
			array_map(
				function ( $booking ) {
					return $booking->ID;
				},
				$bookings
			),
			$bookingIDs
		);

		// make sure, that we get the same bookings when we leave out info about the location and item
		$bookings = Booking::getByTimerange(
			strtotime( '+60 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+70 days', strtotime( self::CURRENT_DATE ) ),
		);
		$this->assertCount( 10, $bookings );
		$this->assertEqualsCanonicalizing(
			array_map(
				function ( $booking ) {
					return $booking->ID;
				},
				$bookings
			),
			$bookingIDs
		);

		// THIS DOES NOT BELONG HERE, REMOVE LATER: TODO
		$bookings = \CommonsBooking\Repository\Timeframe::getInRangePaginated(
			strtotime( '+60 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+70 days', strtotime( self::CURRENT_DATE ) ),
			1,
			10,
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ],
		);
		$this->assertCount( 10, $bookings['posts'] );
		$this->assertEqualsCanonicalizing(
			array_map(
				function ( $booking ) {
					return $booking->ID;
				},
				$bookings['posts']
			),
			$bookingIDs
		);
	}
	public function testGetForUsersPaginated() {
		// let's use the subscriber here to not get confused with the other tests
		$this->createSubscriber();
		$subscriber = get_user_by( 'id', $this->subscriberId );
		$nextWeek   = new \CommonsBooking\Model\Booking(
			$this->createBooking(
				$this->testLocation,
				$this->testItem,
				strtotime( '+8 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+9 days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		Booking::getForUserPaginated( $subscriber );
		$this->assertCount( 1, Booking::getForUserPaginated( $subscriber ) );
		$this->assertCount( 0, Booking::getForUserPaginated( $subscriber, 2 ) );

		// now, let's automatically create a whole bunch of bookings for the subscriber
		$bookingIds = [ $nextWeek->ID ];
		for ( $i = 0; $i < 20; $i++ ) {
			$bookingIds[] = $this->createBooking(
				$this->testLocation,
				$this->testItem,
				strtotime( '+' . ( $i + 10 ) . ' day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 11 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			);
		}
		$this->assertCount( 21, Booking::getForUserPaginated( $subscriber, 1, 21 ) );
		// all should fit on one page
		$this->assertCount( 0, Booking::getForUserPaginated( $subscriber, 2, 21 ) );

		$firstPage  = Booking::getForUserPaginated( $subscriber, 1, 10 );
		$secondPage = Booking::getForUserPaginated( $subscriber, 2, 10 );
		$thirdPage  = Booking::getForUserPaginated( $subscriber, 3, 10 );

		$firstPage  = array_map(
			function ( $booking ) {
				return $booking->ID;
			},
			$firstPage
		);
		$secondPage = array_map(
			function ( $booking ) {
				return $booking->ID;
			},
			$secondPage
		);
		$thirdPage  = array_map(
			function ( $booking ) {
				return $booking->ID;
			},
			$thirdPage
		);

		// make sure, that there are no duplicates
		$this->assertEmpty( array_intersect( $firstPage, $secondPage, $thirdPage ) );
		$this->assertCount( 10, $firstPage );
		$this->assertCount( 10, $secondPage );
		$this->assertCount( 1, $thirdPage );
		$allTogether = array_merge( $firstPage, $secondPage, $thirdPage );
		$this->assertEqualsCanonicalizing( $allTogether, $bookingIds );
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
		$this->assertCount(
			0,
			Booking::get(
				[],
				[],
				date( 'Y-m-d', strtotime( '+200 days', strtotime( self::CURRENT_DATE ) ) )
			)
		);

		// Test date param and make sure there we find bookings for today
		$this->assertCount(
			2,
			Booking::get(
				[],
				[],
				date( 'Y-m-d', strtotime( self::CURRENT_DATE ) )
			)
		);

		// Test minimum timestamp param and make sure we find bookings tomorrow
		$this->assertCount(
			2,
			Booking::get(
				[],
				[],
				null,
				false,
				strtotime( '+1 days', strtotime( self::CURRENT_DATE ) )
			)
		);

		// Test minimum timestamp param; there shouldn't be bookings in 200 days
		$this->assertCount(
			0,
			Booking::get(
				[],
				[],
				null,
				false,
				strtotime( '+200 days', strtotime( self::CURRENT_DATE ) )
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
		$restriction = new \CommonsBooking\Model\Restriction( $this->restriction );
		$this->assertCount( 1, Booking::getByRestriction( $restriction ) );

		// Negative test, restriction is in future of all bookings, shouldn't find one
		$restriction = new \CommonsBooking\Model\Restriction( $this->restriction2 );
		$this->assertCount( 0, Booking::getByRestriction( $restriction ) );
	}

	public function testGetOrphaned() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// create a new booking on a new timeframe and orphan it
		$newLocation       = $this->createLocation( 'newlocation', 'publish' );
		$newItem           = $this->createItem( 'newitem', 'publish' );
		$newTimeframe      = $this->createBookableTimeFrameIncludingCurrentDay( $newLocation, $newItem );
		$newBooking        = $this->createBooking(
			$newLocation,
			$newItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
		);
		$evenNewerLocation = $this->createLocation( 'evennewerlocation', 'publish' );
		update_post_meta( $newTimeframe, 'location-id', $evenNewerLocation );

		// now retrieve all orphaned bookings and make sure we find the new one
		$orphanedBookings = Booking::getOrphaned( null, [ $newItem ] );
		$this->assertCount( 1, $orphanedBookings );
		$this->assertEquals( $newBooking, reset( $orphanedBookings )->ID );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->confirmedBookingEndingToday   = parent::createConfirmedBookingEndingToday();
		$this->confirmedBookingStartingToday = parent::createConfirmedBookingStartingToday();
		$this->testItem                      = parent::createItem( 'testitem', 'publish' );
		$this->testLocation                  = parent::createLocation( 'testlocation', 'publish' );
		$this->testTimeframe                 = $this->createBookableTimeFrameIncludingCurrentDay( $this->testLocation, $this->testItem );

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

	protected function tearDown(): void {
		parent::tearDown();
	}
}
