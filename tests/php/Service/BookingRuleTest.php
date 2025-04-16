<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Service\BookingRule;
use SlopeIt\ClockMock\ClockMock;

class BookingRuleTest extends CustomPostTypeTest {

	protected $testBooking;
	protected BookingRule $alwaysdeny;
	protected BookingRule $alwaysallow;

	public function test__construct() {
		$this->assertNotNull(
			new BookingRule(
				'testRule',
				'test',
				'Testing rule creation',
				'Error message',
				function ( \CommonsBooking\Model\Booking $booking, array $params ) {
					return null;
				},
				array(
					'First param description',
					'Second param description',
				)
			)
		);
	}

	public function testCheckSimultaneousBookings() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$testBookingOne          = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$itemtwo                 = $this->createItem( 'Item2', 'publish' );
		$locationtwo             = $this->createLocation( 'Location2', 'publish' );
		$this->secondTimeframeId = $this->createTimeframe(
			$locationtwo,
			$itemtwo,
			strtotime( '-5 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ) ),
		);
		$testBookingTwo          = new Booking(
			get_post(
				$this->createBooking(
					$locationtwo,
					$itemtwo,
					strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		// we create one booking that is not simultaneous to test that it is not returned
		$testBookingThree = new Booking(
			get_post(
				$this->createBooking(
					$locationtwo,
					$itemtwo,
					strtotime( '+3 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( array( $testBookingOne ), BookingRule::checkSimultaneousBookings( $testBookingTwo ) );
		$this->tearDownAllBookings();
	}

	public function testCheckChainBooking() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$testBookingOne = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$testBookingTwo = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+4 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+5 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( array( $testBookingOne ), BookingRule::checkChainBooking( $testBookingTwo ) );
	}

	public function testGetRulesForSelect() {
		$selectRules = BookingRule::getRulesForSelect();
		$this->assertIsArray( $selectRules );
		// check, that it is also an associative array
		$this->assertArrayHasKey( 'noSimultaneousBooking', $selectRules );
	}

	public function testGetRulesJSON() {
		$rules = BookingRule::getRulesJSON();
		$this->assertIsString( $rules );
		json_decode( $rules );
		$this->assertEquals( JSON_ERROR_NONE, json_last_error() );
	}

	/**
	 * Tests the case where the booking would fulfill the middle of a chain and should therefore be denied
	 * @return void
	 */
	public function testCheckChainLeftRightBooking() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// set the timeframe MaxDays a bit higher so we can properly test the chain
		update_post_meta( $this->firstTimeframeId, 'timeframe-max-days', 5 );
		$beforeBooking = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '-3 days', strtotime( self::CURRENT_DATE ) ),
					strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$afterBooking  = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+3 days', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		// just check that both are allowed
		$this->assertNull( BookingRule::checkChainBooking( $beforeBooking ) );
		$this->assertNull( BookingRule::checkChainBooking( $afterBooking ) );
		$testBooking = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
					strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( array( $beforeBooking, $afterBooking ), BookingRule::checkChainBooking( $testBooking ) );
	}

	public function testCheckMaxBookingDays() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$testBookingOne = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+1 day' ),
					strtotime( '+2 days' ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$testBookingTwo = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+4 day' ),
					strtotime( '+5 days' ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);

		$testBookingThree = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '+6 day' ),
					strtotime( '+7 days' ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( array( $testBookingOne, $testBookingTwo ), BookingRule::checkMaxBookingDays( $testBookingThree, array( 2, 30 ) ) );
	}

	public function testCheckMaxBookingDays_earlyCancel() {
		Settings::updateOption( 'commonsbooking_options_restrictions', 'bookingrules-count-cancelled', 'on' );
		// we create a scenario, where a user makes a booking in a week but cancels it before beginning of start
		$currentDate = new \DateTime( self::CURRENT_DATE );
		ClockMock::freeze( $currentDate );
		$cancelBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+7 days' ),
				strtotime( '+10 days' ),
				'8:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		// two days pass and they cancel it
		$currentDate->modify( '+2 days' );
		ClockMock::freeze( $currentDate );
		$this->cancelBooking( $cancelBooking );
		// now we create a booking that should be allowed
		$allowedBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+6 days' ),
				strtotime( '+9 days' ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->subscriberId
			)
		);
		$this->assertNull( BookingRule::checkMaxBookingDays( $allowedBooking, array( 4, 30 ) ) );
	}

	public function testCheckMaxBookingDaysPerWeek() {
		// rule settings
		$allowedPerWeek = 2;
		$resetDay       = '0'; // sunday
		$optionsArray   = array(
			$allowedPerWeek,
			null,
			$resetDay,
		);

		$nextWeekDate = new \DateTime( self::CURRENT_DATE );
		// we add one week here so that it does not interfere with the bookings of the other tests
		$nextWeekDate->modify( '+1 week' );
		$testBookingOne = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'monday this week', $nextWeekDate->getTimestamp() ),
					strtotime( 'tuesday this week', $nextWeekDate->getTimestamp() ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$testBookingTwo = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'wednesday this week', $nextWeekDate->getTimestamp() ),
					strtotime( 'thursday this week', $nextWeekDate->getTimestamp() ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);

		$testBookingThree    = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'friday this week', $nextWeekDate->getTimestamp() ),
					strtotime( 'saturday this week', $nextWeekDate->getTimestamp() ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$mondayFollowingWeek = clone $nextWeekDate;
		$mondayFollowingWeek->modify( 'monday this week' );
		$mondayFollowingWeek->modify( '+1 week' );

		$tuesdayFollowingWeek = clone $nextWeekDate;
		$tuesdayFollowingWeek->modify( 'tuesday this week' );
		$tuesdayFollowingWeek->modify( '+1 week' );

		$testBookingFour = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					$mondayFollowingWeek->getTimestamp(),
					$tuesdayFollowingWeek->getTimestamp(),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);

		$this->assertBookingsPresent(
			array( $testBookingOne, $testBookingTwo ),
			BookingRule::checkMaxBookingDaysPerWeek(
				$testBookingThree,
				$optionsArray
			)
		);
		$this->assertNull( BookingRule::checkMaxBookingDaysPerWeek( $testBookingFour, $optionsArray ) );
	}

	public function testRegularMaxBookingDaysPerMonth() {
		// we chose a different year than the self::CURRENT_DATE to make sure that the test does not interfere with the other tests
		$testYear  = 2022;
		$testMonth = '05';

		$maxDaysPerMonth         = 5;
		$resetDay                = 1;
		$confirmedBookingObjects = array(
			array(
				'start' => strtotime( '01.' . $testMonth . '.' . $testYear ),
				'end' => strtotime( '04.' . $testMonth . '.' . $testYear ),
			),
			array(
				'start' => strtotime( '05.' . $testMonth . '.' . $testYear ),
				'end' => strtotime( '06.' . $testMonth . '.' . $testYear ),
			),
		);
		$confirmedBookingObjects = $this->createBookingsFromDates( $confirmedBookingObjects );
		$deniedBooking           = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '07.' . $testMonth . '.' . $testYear ),
					strtotime( '09.' . $testMonth . '.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$testMonth               = '0' . ( intval( $testMonth ) - 1 );
		$allowedBooking          = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '20.' . $testMonth . '.' . $testYear ),
					strtotime( '22.' . $testMonth . '.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( $confirmedBookingObjects, BookingRule::checkMaxBookingDaysPerMonth( $deniedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
		$this->assertNull( BookingRule::checkMaxBookingDaysPerMonth( $allowedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
	}

	public function testResetDayMaxBookingDaysPerMonth() {
		// check if the reset day is working
		$testYear                = 2022;
		$maxDaysPerMonth         = 3;
		$resetDay                = 5;
		$testMonth               = '06';
		$previousMonthBooking    = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '01.' . $testMonth . '.' . $testYear ),
					strtotime( '04.' . $testMonth . '.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$confirmedBookingObjects = array(
			array(
				'start' => strtotime( '06.' . $testMonth . '.' . $testYear ),
				'end' => strtotime( '07.' . $testMonth . '.' . $testYear ),
			),
			array(
				'start' => strtotime( '08.' . $testMonth . '.' . $testYear ),
				'end' => strtotime( '10.' . $testMonth . '.' . $testYear ),
			),
		);
		$confirmedBookingObjects = $this->createBookingsFromDates( $confirmedBookingObjects );
		$allowedBooking          = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '03.' . $testMonth . '.' . $testYear ),
					strtotime( '03.' . $testMonth . '.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$disallowedBooking       = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '12.' . $testMonth . '.' . $testYear ),
					strtotime( '13.' . $testMonth . '.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertNull( BookingRule::checkMaxBookingDaysPerMonth( $allowedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
		$this->assertBookingsPresent( $confirmedBookingObjects, BookingRule::checkMaxBookingDaysPerMonth( $disallowedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
	}

	public function testFebruaryMaxBookingDaysPerMonth() {
		// check if the month of february is working when the reset day has exceeded the number of days in the month
		$testYear                = 2022;
		$maxDaysPerMonth         = 4;
		$resetDay                = 31;
		$confirmedBookingObjects = array(
			array(
				'start' => strtotime( '01.02.' . $testYear ),
				'end' => strtotime( '03.02.' . $testYear ),
			),
			array(
				'start' => strtotime( '03.02.' . $testYear ),
				'end' => strtotime( '05.02.' . $testYear ),
			),
		);
		$confirmedBookingObjects = $this->createBookingsFromDates( $confirmedBookingObjects );
		$deniedBooking           = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '05.02.' . $testYear ),
					strtotime( '06.02.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$allowedBooking          = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '01.03.' . $testYear ),
					strtotime( '02.03.' . $testYear ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);

		$this->assertNull( BookingRule::checkMaxBookingDaysPerMonth( $allowedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
		$this->assertBookingsPresent( $confirmedBookingObjects, BookingRule::checkMaxBookingDaysPerMonth( $deniedBooking, array( $maxDaysPerMonth, 0, $resetDay ) ) );
	}


	public function testCheckMaxBookingsPerWeek() {
		$allowedPerWeek      = 1;
		$resetDay            = 1; // sunday
		$optionsArray        = array(
			$allowedPerWeek,
			null,
			$resetDay,
		);
		$testBookingOne      = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'monday this week' ),
					strtotime( 'tuesday this week' ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$testBookingSameWeek = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'wednesday this week' ),
					strtotime( 'thursday this week' ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( [ $testBookingOne ], BookingRule::checkMaxBookingsWeek( $testBookingSameWeek, $optionsArray ) );

		$testBookingNextWeek = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( 'monday next week' ),
					strtotime( 'tuesday next week' ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);

		$this->assertNull( BookingRule::checkMaxBookingsWeek( $testBookingNextWeek, $optionsArray ) );
	}

	public function testCheckMaxBookingsMonth() {
		$allowedPerMonth      = 1;
		$resetDay             = 1; // first day of month
		$optionsArray         = array(
			$allowedPerMonth,
			null,
			$resetDay,
		);
		$testBookingOne       = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '01.01.2022' ),
					strtotime( '02.01.2022' ),
					'8:00 AM',
					'12:00 PM',
					'confirmed',
					$this->subscriberId
				)
			)
		);
		$testBookingSameMonth = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '03.01.2022' ),
					strtotime( '04.01.2022' ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);
		$this->assertBookingsPresent( [ $testBookingOne ], BookingRule::checkMaxBookingsMonth( $testBookingSameMonth, $optionsArray ) );

		$testBookingNextMonth = new Booking(
			get_post(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( '01.02.2022' ),
					strtotime( '02.02.2022' ),
					'8:00 AM',
					'12:00 PM',
					'unconfirmed',
					$this->subscriberId
				)
			)
		);

		$this->assertNull( BookingRule::checkMaxBookingsMonth( $testBookingNextMonth, $optionsArray ) );
	}

	/**
	 * This requires that the maxBookingDaysPerWeek function is working
	 * @return void
	 * @throws \Exception
	 */
	public function testCancelledDayCounting() {
		// settings for maxBookingsPerWeek
		$allowedDaysPerWeek = 2;
		$resetDay           = 0; // monday
		$optionsArray       = array( $allowedDaysPerWeek, null, $resetDay );

		// make sure the option is off
		Settings::updateOption( 'commonsbooking_options_restrictions', 'bookingrules-count-cancelled', 'off' );
		// first, we create a booking that can be cancelled with the setting disabled, the other booking should be allowed
		$testMonday    = '03.01.2022';
		$testWednesday = '05.01.2022';
		// This booking goes from monday to wednesday and is cancelled on tuesday
		$cancelledBookingThisWeek = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( $testMonday ),
				strtotime( $testWednesday ),
				'8:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		// We need to use the cancel function here so that the cancellation date is set correctly. In this case we cancel shortly before the booking ends
		$shortlyBeforeEnd = new \DateTime( $testWednesday );
		$shortlyBeforeEnd->modify( '-30 minutes' );
		ClockMock::freeze( $shortlyBeforeEnd );
		$cancelledBookingThisWeek->cancel();

		// this is the booking that would be allowed when cancelled bookings are not counted but should be denied when they are counted
		$testThursday                = '06.01.2022';
		$testSaturday                = '08.01.2022';
		$conditionallyAllowedBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( $testThursday ),
				strtotime( $testSaturday ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->subscriberId
			)
		);
		$this->assertNull( BookingRule::checkMaxBookingDaysPerWeek( $conditionallyAllowedBooking, $optionsArray ) );

		// now, let's enable the option and see if the booking is denied
		Settings::updateOption( 'commonsbooking_options_restrictions', 'bookingrules-count-cancelled', 'on' );
		$this->assertBookingsPresent( array( $cancelledBookingThisWeek ), BookingRule::checkMaxBookingDaysPerWeek( $conditionallyAllowedBooking, $optionsArray ) );
	}

	public function testHasDefaultSettings() {
		Settings::updateOption( 'commonsbooking_options_restrictions', 'rules_group', null );
		$this->assertTrue( BookingRule::hasDefaultSettings() );
	}

	/**
	 * Will check if the IDs of two arrays of booking models match.
	 * We do this because we can not always compare the instances directly and the order of the array is not important
	 * @param Booking[] $expected
	 * @param Booking[] $result
	 *
	 * @return void
	 */
	protected function assertBookingsPresent( array $expected, array $result ) {
		$this->assertEquals( count( $expected ), count( $result ) );
		$resultIds   = array_map(
			function ( Booking $booking ) {
				return $booking->ID;
			},
			$result
		);
		$expectedIds = array_map(
			function ( Booking $booking ) {
				return $booking->ID;
			},
			$expected
		);
		foreach ( $expectedIds as $id ) {
			$this->assertContains( $id, $resultIds );
		}
	}

	/**
	 * @param array $datearray
	 *
	 * @return Booking[]
	 * @throws \Exception
	 */
	protected function createBookingsFromDates( array $datearray ): array {
		$bookings = array();
		foreach ( $datearray as $date ) {
			$bookings[] = new Booking(
				get_post(
					$this->createBooking(
						$this->locationId,
						$this->itemId,
						$date['start'],
						$date['end'],
						'8:00 AM',
						'12:00 PM',
						'confirmed',
						$this->subscriberId
					)
				)
			);
		}
		return $bookings;
	}

	protected function setUp(): void {
		parent::setUp();
		$this->alwaysallow      = new BookingRule(
			'alwaysAllow',
			'Always allow',
			'Rule will always evaluate to null',
			'Rule did not evaluate to null',
			function ( \CommonsBooking\Model\Booking $booking ) {
				return null;
			}
		);
		$this->alwaysdeny       = new BookingRule(
			'alwaysDeny',
			'Always deny',
			'Rule will always deny and return the current booking as conflict',
			'Rule evaluated correctly',
			function ( \CommonsBooking\Model\Booking $booking ) {
				return array( $booking );
			}
		);
		$this->firstTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ) )
		);

		$this->createSubscriber();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
