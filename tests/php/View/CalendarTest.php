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

		$this->assertTrue( $maxBookableDays == ( self::bookingDaysInAdvance - self::timeframeStart - 1 ) );
	}

	public function testClosestBookableTimeFrameFuntion() {
		$startDate = date( 'Y-m-d', strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ) );
		$endDate   = date( 'Y-m-d', strtotime( '+60 days midnight', strtotime( self::CURRENT_DATE ) ) );

		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			$startDate,
			$endDate
		);

		$this->assertTrue( $jsonresponse['minDate'] == date( 'Y-m-d' ) );
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
			date( 'Y-m-d', strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ) ),
			date( 'Y-m-d', strtotime( '+60 days midnight', strtotime( self::CURRENT_DATE ) ) )
		);
		$this->assertTrue( $jsonresponse['disallowLockDaysInRange'] );
		$this->assertFalse( $jsonresponse['countLockDaysInRange'] );
		$this->assertEquals( 0, $jsonresponse['countLockDaysMaxDays'] );

		//old locations which only have overbooking enabled should not have the countLockDaysInRange set and countLockDaysMaxDays should be 0
		$differentItemId = $this->createItem( "Different Item", 'publish' );
		$oldLocationId   = $this->createLocation( "Old Location", 'publish' );
		$otherTimeframe  = $this->createBookableTimeFrameIncludingCurrentDay( $oldLocationId, $differentItemId );
		update_post_meta( $oldLocationId, COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range', 'on' );
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$jsonresponse = Calendar::getCalendarDataArray(
			$differentItemId,
			$oldLocationId,
			date( 'Y-m-d', strtotime( '-1 days', strtotime( self::CURRENT_DATE ) ) ),
			date( 'Y-m-d', strtotime( '+60 days midnight', strtotime( self::CURRENT_DATE ) ) )
		);
		$this->assertFalse( $jsonresponse['disallowLockDaysInRange'] );
		$this->assertFalse( $jsonresponse['countLockDaysInRange'] );
		$this->assertEquals( 0, $jsonresponse['countLockDaysMaxDays'] );
	}

	public function testBookingOffset() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$startDate       = date( 'Y-m-d', strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ) );
		$today           = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		$endDate         = date( 'Y-m-d', strtotime( '+60 days midnight', strtotime( self::CURRENT_DATE ) ) );
		$otherItemId     = $this->createItem( "Other Item", 'publish' );
		$otherLocationId = $this->createLocation( "Other Location", 'publish' );
		$offsetTF        = $this->createTimeframe(
			$otherLocationId,
			$otherItemId,
			strtotime( $startDate ),
			strtotime( $endDate ),
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
		$jsonresponse    = Calendar::getCalendarDataArray(
			$otherItemId,
			$otherLocationId,
			$startDate,
			$endDate
		);
		//considering the advance booking days
		$days = $jsonresponse['days'];
		$this->assertEquals( 32, count( $days ) );
		//considering the offset, today and tomorrow should be locked
		$this->assertTrue( $days[ $today ]['locked'] );
		$this->assertTrue( $days[ date( 'Y-m-d', strtotime( '+1 day', strtotime( $today ) ) ) ]['locked'] );
	}

	public function testRenderTable() {
		$calendar = Calendar::renderTable( [] );
		$item     = new \CommonsBooking\Model\Item( $this->itemId );
		$location = new \CommonsBooking\Model\Location( $this->locationId );
		$this->assertStringContainsString( '<table', $calendar );
		$this->assertStringContainsString( $item->post_title, $calendar );
		$this->assertStringContainsString( $location->post_title, $calendar );

		//in a year, all timeframes will have expired -> calendar should be empty
		$inAYear = new \DateTime();
		$inAYear->modify( '+1 year' );
		ClockMock::freeze( $inAYear );
		$calendar = Calendar::renderTable( [] );
		$this->assertStringContainsString( 'No items found', $calendar );
	}

	/**
	 *
	 * @return array
	 */
	public function provideGetClosestBookableTimeFrameForToday() {
		$currentTimestamp = strtotime( self::CURRENT_DATE . ' 12:00' );
		//will define an array with settings for the timeframes
		//that the getClosestBookableTimeFrameForToday function will be tested against
		//you can provide the name of the test, the closest timeframe and another timeframe.
		//supported arguments for timeframe, if not specified default values will be used
		//repetition, repetition_start, repetition_end = null,weekdays = ["1","2","3","4","5","6","7"], start_time = '8:00 AM', end_time = '12:00 PM'
		//if no start and endtime are provided, the timeframe will span the full day.
		//If they are provided, fullday is turned off.
		//Please note: The date that we test against is a thursday.
		return [
			"daily not overlapping"                        => [
				"closest" => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "-7 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+7 days", $currentTimestamp ),
				],
				"other"   => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "+8 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+14 days", $currentTimestamp ),
				]
			],
			"weekly (different weekdays)"                  => [
				"closest" => [
					"repetition"       => "w",
					"repetition_start" => strtotime( "-7 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+7 days", $currentTimestamp ),
					"weekdays"         => [ "4" ] //just thursday
				],
				"other"   => [
					"repetition"       => "w",
					"repetition_start" => strtotime( "-7 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+7 days", $currentTimestamp ),
					"weekdays"         => [ "1", "2", "3", "5", "6", "7" ] //all but thursday
				]
			],
			"both timeframes in future (daily rep)"        => [
				"closest" => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "+7 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+14 days", $currentTimestamp ),
				],
				"other"   => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "+15 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+21 days", $currentTimestamp ),
				]
			],
			"daily overlap with different times (present)" => [
				"closest" => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "-1 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+1 days", $currentTimestamp ),
					"start_time"       => "8:00 AM",
					"end_time"         => "01:00 PM"
				],
				"other"   => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "-1 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+1 days", $currentTimestamp ),
					"start_time"       => "02:00 PM",
					"end_time"         => "06:00 PM"
				]
			],
			"daily overlap with different times (future)"  => [
				"closest" => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "+5 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+7 days", $currentTimestamp ),
					"start_time"       => "8:00 AM",
					"end_time"         => "01:00 PM"
				],
				"other"   => [
					"repetition"       => "d",
					"repetition_start" => strtotime( "+5 days", $currentTimestamp ),
					"repetition_end"   => strtotime( "+7 days", $currentTimestamp ),
					"start_time"       => "02:00 PM",
					"end_time"         => "06:00 PM"
				],
			]
		];
	}

	/**
	 * These are the tests for timeframes with daily repetition
	 * @return void
	 * @throws \Exception
	 * @dataProvider provideGetClosestBookableTimeFrameForToday
	 */
	public function testGetClosestBookableTimeFrameForToday( array $closest, array $other ) {
		$testItem     = $this->createItem( "Item" );
		$testLocation = $this->createLocation( "Location" );
		$currentTime  = new \DateTime( self::CURRENT_DATE );
		$currentTime->setTime( 12, 0 );
		//Time set to '01.07.2021 12:00'
		ClockMock::freeze( $currentTime );
		$expectedClosestTimeframe = $this->createTimeframeFromConfig( "closest timeframe", $testItem, $testLocation, $closest );
		$otherTimeframe           = $this->createTimeframeFromConfig( "other timeframe", $testItem, $testLocation, $other );
		$closestTimeframe         = Calendar::getClosestBookableTimeFrameForToday( [
			$expectedClosestTimeframe,
			$otherTimeframe
		] );
		$this->assertEquals( $expectedClosestTimeframe->ID, $closestTimeframe->ID );
	}

	/**
	 * Will create the timeframes from the configuration defined in the dataProvider of testGetClosestBookableTimeFrameForToday
	 *
	 * @param int $itemId
	 * @param int $locationID
	 * @param array $config
	 *
	 * @return void
	 */
	private function createTimeframeFromConfig( string $name, int $itemId, int $locationID, array $config ): Timeframe {
		$fullDay = ! ( isset ( $config["start_time"] ) && isset( $config["end_time"] ) );
		$grid    = $fullDay ? 1 : 0; //Currently, grid is becoming hourly when not full day (TODO: Also test slots)

		return new Timeframe(
			$this->createTimeframe(
				$locationID,
				$itemId,
				$config["repetition_start"],
				$config["repetition_end"] ?? null,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				$fullDay ? "on" : "off",
				$config["repetition"],
				$grid,
				$config["start_time"] ?? '8:00 AM',
				$config["end_time"] ?? '12:00 PM',
				"publish",
				$config["weekdays"] ?? [ "1", "2", "3", "4", "5", "6", "7" ],
				"",
				self::USER_ID,
				3,
				30,
				0,
				"on",
				"on",
				$name
			)
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->now         = time();
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
