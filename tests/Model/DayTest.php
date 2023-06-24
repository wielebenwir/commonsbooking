<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class DayTest extends CustomPostTypeTest {

	private $instance;

	protected $bookableTimeframeForCurrentDayId;

	protected $bookableTimeframeNoRepSingleDayTomorrowId;

	protected $bookableTimeframeNoRepSingleDayTodayId;

	protected $bookableTimeframeNoRepStartsYesterdayEndsTomorrowId;

	protected $bookableTimeframeOnceWeeklyValidTodayNoEnd;

	protected $bookableTimeframeOnceWeeklyValidTodayWithEnd;

	protected $bookableTimeframeManualDateInputOnlyForToday;

	protected function setUp() : void {
		parent::setUp();
		$this->bookableTimeframeForCurrentDayId = $this->createBookableTimeFrameIncludingCurrentDay();

		$this->bookableTimeframeNoRepSingleDayTomorrowId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"norep"
		);

		$this->bookableTimeframeNoRepSingleDayTodayId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"norep"
		);

		$this->bookableTimeframeNoRepStartsYesterdayEndsTomorrowId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"norep"
		);

		//get the current weekday of the current date
		$weekday = date('w', strtotime(self::CURRENT_DATE)) + 1; // +1 because the weekdays in the timeframe are 1-7, not 0-6

		$this->bookableTimeframeOnceWeeklyValidTodayNoEnd = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-7 days', self::CURRENT_DATE ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"w",
			0,
			"8:00 AM",
			"12:00 PM",
			"publish",
			[ strval( $weekday ) ]
		);

		$this->bookableTimeframeOnceWeeklyValidTodayWithEnd = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-7 days', self::CURRENT_DATE ),
			strtotime( '+7 days', self::CURRENT_DATE ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"w",
			0,
			"8:00 AM",
			"12:00 PM",
			"publish",
			[ strval( $weekday ) ]
		);

		$currentDayFormatted = date( 'd.m.Y', strtotime( self::CURRENT_DATE ) );
		$this->bookableTimeframeManualDateInputOnlyForToday = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( self::CURRENT_DATE ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			"manual",
			0,
			"8:00 AM",
			"12:00 PM",
			"publish",
			[ "1", "2", "3", "4", "5", "6", "7" ],
			[$currentDayFormatted]
		);

		$this->createUnconfirmedBookingEndingTomorrow();

		$this->instance = new Day(
			self::CURRENT_DATE,
			[ $this->locationId ],
			[ $this->itemId ]
		);
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testGetFormattedDate() {
		$this->assertTrue( self::CURRENT_DATE == $this->instance->getFormattedDate( 'd.m.Y' ) );
	}

	public function testGetDayOfWeek() {
		$this->assertTrue( date('w', strtotime(self::CURRENT_DATE)) == $this->instance->getDayOfWeek() );
	}

	public function testGetDate() {
		$this->assertTrue( self::CURRENT_DATE == $this->instance->getDate() );
	}

	public function testIsInTimeframe() {
		$timeframe = new Timeframe( $this->bookableTimeframeForCurrentDayId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepSingleDayTomorrowId );
		$this->assertFalse( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepSingleDayTodayId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepStartsYesterdayEndsTomorrowId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeOnceWeeklyValidTodayNoEnd );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeOnceWeeklyValidTodayWithEnd );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeManualDateInputOnlyForToday );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );
	}

	public function testGetName() {
		$this->assertTrue( date( 'l', strtotime( self::CURRENT_DATE ) ) == $this->instance->getName() );
	}

	public function testGetTimeframes() {
		// Should only find confirmed timeframes
		$this->assertTrue(count($this->instance->getTimeframes()) == 3);
	}

	public function testGetRestrictions() {
		$this->assertTrue( count($this->instance->getRestrictions()) == 0 );

		$this->createRestriction(
			"hint",
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			strtotime("tomorrow", strtotime(self::CURRENT_DATE))
		);

		$this->assertIsArray($this->instance->getRestrictions());
		$this->assertTrue(count($this->instance->getRestrictions()) == 1);
	}

}
