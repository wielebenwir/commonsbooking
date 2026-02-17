<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Model\Calendar;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use SlopeIt\ClockMock\ClockMock;


/**
 * Tests weekdays
 */
class CalendarTest extends CustomPostTypeTest {

	private Calendar $calendar;
	private \DateTime $now;

	public function testGetDays() {
		$this->calendar = new Calendar( new Day( '2023-05-01' ), new Day( '2023-06-01' ) );
		$this->assertEquals( 5, count( $this->calendar->getWeeks() ) );
		$this->assertEquals(
			array(
				new Week( 2023, 120 ),
				new Week( 2023, 127 ),
				new Week( 2023, 134 ),
				new Week( 2023, 141 ),
				new Week( 2023, 148 ),
			),
			$this->calendar->getWeeks()
		);
	}

	public function testGetAvailabilitySlots() {
		$this->createBookableTimeFrameIncludingCurrentDay();
		$this->createBookableTimeFrameStartingInAWeek();
		$today       = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		$todayEnd    = $this->getEndOfDayTimestamp( self::CURRENT_DATE );
		$tomorrow    = date( 'Y-m-d', strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ) );
		$tomorrowEnd = $this->getEndOfDayTimestamp( $tomorrow );

		$this->calendar    = new Calendar(
			new Day( $today, [ $this->locationID ], [ $this->itemID ] ),
			new Day( $tomorrow, [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();

		$this->assertEquals( 2, count( $availabilitySlots ) );
		$expectedSlotObject = [
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( self::CURRENT_DATE ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', $todayEnd ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( $tomorrow ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', $tomorrowEnd ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
		];
		$this->assertEquals( $expectedSlotObject, $availabilitySlots );

		// now let's book the current day and check, that only tomorrow is available
		$this->createBooking(
			$this->locationID,
			$this->itemID,
			strtotime( $today ),
			$todayEnd
		);
		// recreate the calendar object to get the updated availability
		$this->calendar    = new Calendar(
			new Day( $today, [ $this->locationID ], [ $this->itemID ] ),
			new Day( $tomorrow, [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$this->assertEquals( 1, count( $availabilitySlots ) );
		$expectedSlotObject = [
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( $tomorrow ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', $tomorrowEnd ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
		];
		$this->assertEquals( $expectedSlotObject, $availabilitySlots );
	}

	public function testGetAvailabilitySlotsWithHourlyTimeframe() {
		$this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( self::CURRENT_DATE ),
			Timeframe::BOOKABLE_ID,
			'off',
			'd',
			1,
			'8:00 AM',
			'8:00 PM'
		);
		$start = new \DateTime( self::CURRENT_DATE );
		$start->setTime( 8, 0, 0 );
		$end = new \DateTime( self::CURRENT_DATE );
		$end->setTime( 20, 0, 0 );
		$expectedPeriod = new \DatePeriod(
			$start,
			new \DateInterval( 'PT1H' ),
			$end
		);
		$this->calendar = new Calendar(
			new Day( $start->format( 'Y-m-d' ), [ $this->locationID ], [ $this->itemID ] ),
			// we do this because the calendar needs to span at least one day
			new Day( date( 'Y-m-d', strtotime( '+1 day', $end->getTimestamp() ) ), [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$this->assertEquals( iterator_count( $expectedPeriod ), count( $availabilitySlots ) );

		// book two hours and check that the slots are not available
		$this->createBooking(
			$this->locationID,
			$this->itemID,
			strtotime( '10:00 AM', strtotime( self::CURRENT_DATE ) ),
			strtotime( '01:00 PM', strtotime( self::CURRENT_DATE ) ),
			'10:00 AM',
			'01:00 PM'
		);
		// re-create calendar object to reflect changes
		$this->calendar    = new Calendar(
			new Day( $start->format( 'Y-m-d' ), [ $this->locationID ], [ $this->itemID ] ),
			new Day( date( 'Y-m-d', strtotime( '+1 day', $end->getTimestamp() ) ), [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$this->assertEquals( iterator_count( $expectedPeriod ) - 3, count( $availabilitySlots ) );
	}

	public function testGetAvailabilitySlotsWithOffset() {
		// check with offset, first two days should not be marked as bookable
		$offsetTF            = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 week', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'd',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			'',
			self::USER_ID,
			3,
			30,
			2
		);
		$this->calendar      = new Calendar(
			new Day( $this->now->format( 'Y-m-d' ), [ $this->locationID ], [ $this->itemID ] ),
			new Day( date( 'Y-m-d', strtotime( '+1 weeks', strtotime( self::CURRENT_DATE ) ) ), [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots   = $this->calendar->getAvailabilitySlots();
		$expectedSlotsObject = [
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+2 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+2 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+3 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+3 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+4 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+4 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+5 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+5 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+6 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+6 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+7 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+7 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
		];
		$this->assertEquals( $expectedSlotsObject, $availabilitySlots );
	}

	public function testGetAvailabilitySlotsWithHoliday() {
		$this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
		);
		$this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			Timeframe::HOLIDAYS_ID
		);
		$this->calendar      = new Calendar(
			new Day( $this->now->format( 'Y-m-d' ), [ $this->locationID ], [ $this->itemID ] ),
			new Day( date( 'Y-m-d', strtotime( '+1 week', strtotime( self::CURRENT_DATE ) ) ), [ $this->locationID ], [ $this->itemID ] ),
			[ $this->locationID ],
			[ $this->itemID ]
		);
		$availabilitySlots   = $this->calendar->getAvailabilitySlots();
		$expectedSlotsObject = [
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+2 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+2 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+3 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+3 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
			(object) [
				'start'      => date( 'Y-m-d\TH:i:sP', strtotime( '+4 days', $this->now->getTimestamp() ) ),
				'end'        => date( 'Y-m-d\TH:i:sP', strtotime( '+4 days 23:59:59', $this->now->getTimestamp() ) ),
				'itemId'     => $this->itemID,
				'locationId' => $this->locationID,
			],
		];
		$this->assertEquals( $expectedSlotsObject, $availabilitySlots );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->now = new \DateTime( self::CURRENT_DATE );
		ClockMock::freeze( $this->now );
	}
}
