<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Model\Calendar;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;


/**
 * Tests weekdays
 */
class CalendarTest extends CustomPostTypeTest {

	private Calendar $calendar;

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
		$yesterday = date( 'Y-m-d', strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ) );
		$tomorrow = date( 'Y-m-d', strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ) );
		$this->calendar = new Calendar(
			new Day($yesterday, [$this->locationId], [$this->itemId]),
			new Day($tomorrow, [$this->locationId], [$this->itemId]),
			[ $this->locationId ],
			[ $this->itemId ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$yesterdayEnd = $this->getEndOfDayTimestamp($yesterday);
		$todayEnd = $this->getEndOfDayTimestamp(self::CURRENT_DATE);
		$tomorrowEnd = $this->getEndOfDayTimestamp($tomorrow);

		$this->assertEquals( 3, count( $availabilitySlots ) );
		$expectedSlotObject = [
			(object) [
				'start' => date('Y-m-d\TH:i:sP', strtotime($yesterday)),
				'end' => date('Y-m-d\TH:i:sP', $yesterdayEnd),
				'itemId' => $this->itemId,
				'locationId' => $this->locationId
			],
			(object) [
				'start' => date('Y-m-d\TH:i:sP', strtotime(self::CURRENT_DATE)),
				'end' => date('Y-m-d\TH:i:sP', $todayEnd),
				'itemId' => $this->itemId,
				'locationId' => $this->locationId
			],
			(object) [
				'start' => date('Y-m-d\TH:i:sP', strtotime($tomorrow)),
				'end' => date('Y-m-d\TH:i:sP', $tomorrowEnd),
				'itemId' => $this->itemId,
				'locationId' => $this->locationId
			]
		];
		$this->assertEquals($expectedSlotObject, $availabilitySlots);

		//now let's book the current day and check, that only yesterday is available
		$this->createConfirmedBookingStartingToday();
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$this->assertEquals( 1, count( $availabilitySlots ) );
		$expectedSlotObject = [
			(object) [
				'start' => date('Y-m-d\TH:i:sP', strtotime($yesterday)),
				'end' => date('Y-m-d\TH:i:sP', $yesterdayEnd),
				'itemId' => $this->itemId,
				'locationId' => $this->locationId
			]
		];
		$this->assertEquals($expectedSlotObject, $availabilitySlots);

		//test with hourly available timeframe, we create a new item and location for that test to prevent overlapping with next week
		$itemID = $this->createItem("Hourly Item",'publish');
		$locationID = $this->createLocation("Hourly Location",'publish');
		$this->createTimeframe(
			$locationID,
			$itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( self::CURRENT_DATE ),
			Timeframe::BOOKABLE_ID,
			'off',
			"d",
			1,
			'8:00 AM',
			'12:00 PM'
		);
		$start = new \DateTime( self::CURRENT_DATE);
		$start->setTime(8,0,0);
		$end = new \DateTime( self::CURRENT_DATE);
		$end->setTime(24,0,0);
		$expectedPeriod = new \DatePeriod(
			$start,
			new \DateInterval('PT1H'),
			$end
		);
		$this->calendar = new Calendar(
			new Day($start->format('Y-m-d'), [$locationID], [$itemID]),
			//we do this because the calendar needs to span at least one day
			new Day(date('Y-m-d', strtotime('+1 day', $end->getTimestamp())), [$locationID], [$itemID]),
			[ $locationID ],
			[ $itemID ]
		);
		$availabilitySlots = $this->calendar->getAvailabilitySlots();
		$this->assertEquals( iterator_count($expectedPeriod), count( $availabilitySlots ) );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->createBookableTimeFrameIncludingCurrentDay();
		$this->createBookableTimeFrameStartingInAWeek();
	}
}
