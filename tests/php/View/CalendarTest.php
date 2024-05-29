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

	protected $closestTimeframe;

	protected $secondClosestTimeframe;

	private $now;

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

	/*
	 * Make sure, that the default values for overbooking are passed to the Litepicker correctly,
	 * even when not all of them are set. (tests #1393)
	 */
	public function testOverbookingDefaultValues() {
		//the default location has no overbooking values set, overbooking should be disabled
		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			date( 'Y-m-d', strtotime( 'midnight', strtotime(self::CURRENT_DATE) ) ),
			date( 'Y-m-d', strtotime( '+60 days midnight', strtotime(self::CURRENT_DATE) ) )
		);
		$this->assertTrue($jsonresponse['disallowLockDaysInRange']);
		$this->assertFalse($jsonresponse['countLockDaysInRange']);
		$this->assertEquals(0, $jsonresponse['countLockDaysMaxDays']);

		//old locations which only have overbooking enabled should not have the countLockDaysInRange set and countLockDaysMaxDays should be 0
		$differentItemId = $this->createItem("Different Item",'publish');
		$oldLocationId = $this->createLocation("Old Location",'publish');
		$otherTimeframe = $this->createBookableTimeFrameIncludingCurrentDay($oldLocationId,$differentItemId);
		update_post_meta( $oldLocationId, COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range', 'on' );
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ));
		$jsonresponse = Calendar::getCalendarDataArray(
			$differentItemId,
			$oldLocationId,
			date( 'Y-m-d', strtotime( '-1 days', strtotime(self::CURRENT_DATE) ) ),
			date( 'Y-m-d', strtotime( '+60 days midnight', strtotime(self::CURRENT_DATE) ) )
		);
		$this->assertFalse($jsonresponse['disallowLockDaysInRange']);
		$this->assertFalse($jsonresponse['countLockDaysInRange']);
		$this->assertEquals(0, $jsonresponse['countLockDaysMaxDays']);
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
			'',
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

	public function testRenderTable() {
		$calendar = Calendar::renderTable([]);
		$item = new \CommonsBooking\Model\Item($this->itemId);
		$location = new \CommonsBooking\Model\Location($this->locationId);
		$this->assertStringContainsString('<table', $calendar);
		$this->assertStringContainsString($item->post_title, $calendar);
		$this->assertStringContainsString($location->post_title, $calendar);

		//in a year, all timeframes will have expired -> calendar should be empty
		$inAYear = new \DateTime();
		$inAYear->modify('+1 year');
		ClockMock::freeze($inAYear);
		$calendar = Calendar::renderTable([]);
		$this->assertStringContainsString('No items found', $calendar);
	}

	public function testGetClosestBookableTimeFrameForToday() {
		//Case 1: Timeframes do not overlap
		$closestTimeframeModel = new Timeframe( $this->closestTimeframe );
		$secondClosestTimeframeModel = new Timeframe( $this->secondClosestTimeframe );
		$timeframes = [ $closestTimeframeModel, $secondClosestTimeframeModel ];
		$this->assertEquals($closestTimeframeModel->ID, Calendar::getClosestBookableTimeFrameForToday($timeframes)->ID );

		//Case 2: Timeframes overlap
		$otherLocation = $this->createLocation("OtherLocation");
		$otherItem = $this->createItem("OtherItem");
		$continuedTimeframe = new Timeframe( $this->createTimeframe(
			$otherLocation,
			$otherItem,
			strtotime( '-50 days midnight', $this->now ),
			strtotime( '+365 days midnight', $this->now ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ "1", "2", "3", "4" ],
		) );
		$inBetweenTimeframe = new Timeframe( $this->createTimeframe(
			$otherLocation,
			$otherItem,
			strtotime( '+20 days midnight', $this->now ),
			strtotime( '+40 days midnight', $this->now ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ "5", "6" ],
		) );
		$timeframes = [ $inBetweenTimeframe, $continuedTimeframe ];
		$this->assertEquals($continuedTimeframe->ID, Calendar::getClosestBookableTimeFrameForToday($timeframes)->ID);

	}

	protected function setUp() : void {
		parent::setUp();

		$this->now               = time();
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+' . self::timeframeStart . ' days midnight', $this->now ),
			strtotime( '+' . self::timeframeEnd . ' days midnight', $this->now )
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
