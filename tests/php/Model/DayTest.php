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
			strtotime(self::CURRENT_DATE),
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

	public function testGetBookableItems() {
		$this->assertCount( 1, $this->instance->getBookableItems() );

		//create separate item / location for the other tests because that norep stuff seems broken to me
		$secondLocation = $this->createLocation("second location",'publish');
		$secondItem = $this->createItem("second item",'publish');
		$this->createBookableTimeFrameIncludingCurrentDay($secondLocation,$secondItem);
		$day = new Day(
			self::CURRENT_DATE,
			[ $secondLocation ],
			[ $secondItem ]
		);
		$this->assertCount( 1, $day->getBookableItems() );

		$inTwoDays = new Day(
			date( 'Y-m-d', strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ) ),
			[ $secondLocation ],
			[ $secondItem ]
		);
		$this->assertEmpty( $inTwoDays->getBookableItems() );

		//now, let's book tomorrow
		$this->createConfirmedBookingStartingToday($secondLocation,$secondItem);
		$this->assertEmpty( $day->getBookableItems() );
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
