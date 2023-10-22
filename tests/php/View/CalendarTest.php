<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Calendar;
use DateTime;
use SlopeIt\ClockMock\ClockMock;

/**
 * @TODO: Write test for restriction cache invalidation.
 */

class CalendarTest extends CustomPostTypeTest {

	protected const bookingDaysInAdvance = 35;

	protected const timeframeStart = 29;

	protected const timeframeEnd = 100;

	protected $timeframeId;

	public function testKeepDateRangeParam() {
		$startDate    = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			$startDate,
			date( 'Y-m-d', strtotime( '+20 days', strtotime( self::CURRENT_DATE ) ) ),
			true
		);

		$dayKeys  = array_keys( $jsonresponse['days'] );
		$firstDay = array_shift( $dayKeys );
		$this->assertTrue( $firstDay == $startDate );
	}

	public function testAdvancedBookingDays() {
		$startDate    = date( 'Y-m-d', strtotime( 'midnight' ) );
		$endDate      = date( 'Y-m-d', strtotime( '+60 days midnight' ) );
		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			$startDate,
			$endDate
		);

		$jsonReponseBookableDaysOnly = array_filter( $jsonresponse['days'], function ( $day ) {
			return ! $day['locked'];
		} );

		// Timeframe starting in future, starts in range of calendar, ends out of calendar range
		$timeframe = new Timeframe( $this->timeframeId );

		// start date of timerange
		$timeframeStart = new DateTime();
		$timeframeStart->setTimestamp( $timeframe->getStartDate() );

		// latest possible booking date
		$latestPossibleBookingDateTimestamp = $timeframe->getLatestPossibleBookingDateTimestamp();
		$latestPossibleBookingDate          = new DateTime();
		$latestPossibleBookingDate->setTimestamp( $latestPossibleBookingDateTimestamp );

		// days between start date and latest possible booking date
		$maxBookableDays = date_diff( $latestPossibleBookingDate, $timeframeStart )->days;

		$this->assertTrue( $maxBookableDays == (self::bookingDaysInAdvance - self::timeframeStart - 1) );
	}

	public function testClosestBookableTimeFrameFuntion() {
		$startDate    = date( 'Y-m-d', strtotime( 'midnight', strtotime(self::CURRENT_DATE) ) );
		$endDate      = date( 'Y-m-d', strtotime( '+60 days midnight', strtotime(self::CURRENT_DATE) ) );

		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			$startDate,
			$endDate
		);

		$this->assertTrue($jsonresponse['minDate'] == date('Y-m-d'));
	}

	public function testBookingOffset() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ));
		$startDate = date( 'Y-m-d', strtotime( '-1 day', strtotime(self::CURRENT_DATE) ) );
		$today = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		$endDate   = date( 'Y-m-d', strtotime( '+60 days midnight', strtotime(self::CURRENT_DATE) ) );
		$otherItemId = $this->createItem("Other Item",'publish');
		$otherLocationId = $this->createLocation("Other Location",'publish');
		$offsetTF = $this->createTimeframe(
			$otherLocationId,
			$otherItemId,
			strtotime($startDate),
			strtotime($endDate),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'd',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			self::USER_ID,
			3,
			30,
			2
		);
		$jsonresponse = Calendar::getCalendarDataArray(
			$otherItemId,
			$otherLocationId,
			$startDate,
			$endDate
		);
		//considering the advance booking days
		$days = $jsonresponse['days'];
		$this->assertEquals(32, count($days));
		//considering the offset, today and tomorrow should be locked
		$this->assertTrue($days[$today]['locked']);
		$this->assertTrue($days[date('Y-m-d', strtotime('+1 day', strtotime($today)))]['locked']);
	}

	protected function setUp() : void {
		parent::setUp();

		$now               = time();
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+' . self::timeframeStart . ' days midnight', $now ),
			strtotime( '+' . self::timeframeEnd . ' days midnight', $now )
		);
		// set booking days in advance
		update_post_meta( $this->timeframeId, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		$this->closestTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-100 days midnight' ),
			strtotime( '+13 days midnight' )
		);

		$this->secondClosestTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+14 days midnight' ),
			strtotime( '+300 days midnight' )
		);
	}

}
