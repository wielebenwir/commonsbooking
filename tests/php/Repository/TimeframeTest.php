<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class TimeframeTest extends CustomPostTypeTest {

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	protected int $timeframeId;
	protected int $timeframe2Id;

	public function testGetInRange() {
		$inRangeTimeFrames = Timeframe::getInRange(self::REPETITION_START, self::REPETITION_END);
		$this->assertTrue(count($inRangeTimeFrames) == 2);
		$postIds = array_map(function($timeframe) {
			return $timeframe->ID;
		}, $inRangeTimeFrames);
		$this->assertContains($this->timeframeId, $postIds);
		$this->assertContains($this->timeframe2Id, $postIds);
	}

	public function testGetForItem() {
		$inItemTimeframes = Timeframe::get(
			[],
			[$this->itemId],
		);
		$this->assertEquals(2,count($inItemTimeframes));
		$postIds = array_map(function($timeframe) {
			return $timeframe->ID;
		}, $inItemTimeframes);
		$this->assertContains($this->timeframeId, $postIds);
		$this->assertContains($this->timeframe2Id, $postIds);
	}

	public function testGetForLocation() {
		$inLocationTimeframes = Timeframe::get(
			[$this->locationId],
		);
		$this->assertEquals(2,count($inLocationTimeframes));
		$postIds = array_map(function($timeframe) {
			return $timeframe->ID;
		}, $inLocationTimeframes);
		$this->assertContains($this->timeframeId, $postIds);
		$this->assertContains($this->timeframe2Id, $postIds);
	}

	public function testGetForLocationAndItem() {
		$inLocationAndItemTimeframes = Timeframe::get(
			[$this->locationId],
			[$this->itemId],
		);
		$this->assertEquals(2,count($inLocationAndItemTimeframes));
		$postIds = array_map(function($timeframe) {
			return $timeframe->ID;
		}, $inLocationAndItemTimeframes);
		$this->assertContains($this->timeframeId, $postIds);
		$this->assertContains($this->timeframe2Id, $postIds);
	}

	/**
	 * Will check if we can get a timeframe of the holiday type just the same as a normal timeframe
	 * @return void
	 */
	public function testGetHoliday() {
		$holidayId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE )),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID
		);
		$allTimeframesForLocAndItem = Timeframe::get(
			[$this->locationId],
			[$this->itemId],
		);
		$this->assertEquals(3,count($allTimeframesForLocAndItem));
		$this->assertEqualsCanonicalizing(
			[$this->timeframeId, $this->timeframe2Id, $holidayId],
			array_map(function($timeframe) {
				return $timeframe->ID;
			}, $allTimeframesForLocAndItem)
		);

		//Test-case for #1357 . The holiday should be returned regardless of the 'maxBookingDays'(aka advanceBookingDays) setting for the holiday. The maxBookingDays setting is only applicable for bookable timeframes.
		//We remove the irrelevant postmeta so that it is not processed by the filtering functions anymore
		$holidayInFuture = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+61 days', strtotime( self::CURRENT_DATE )),
			strtotime( '+62 days', strtotime( self::CURRENT_DATE )),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
			"on",
			"d",
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			self::USER_ID,
			3,
			30
		);
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::savePost(
			$holidayInFuture,
			get_post($holidayInFuture)
		);
		//This is necessary, because the getLatestPossibleBookingDateTimestamp takes time() as the calculation base.
		//the getLatestPossibleBookingDateTimestamp function takes the current time and adds the extra days on top to determine at what day you are allowed to book.
		//Because our CURRENT_DATE is so far in the past, the latest possible booking date is also very far in the past which means that the test would not fail for a broken filterTimeframesByMaxBookingDays function.
		//Therefore we have to freeze the time or else the test would make no sense.
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$allTimeframesForLocAndItem = Timeframe::get(
			[$this->locationId],
			[$this->itemId],
		);
		$this->assertEquals(4,count($allTimeframesForLocAndItem));
		$this->assertEqualsCanonicalizing(
			[$this->timeframeId, $this->timeframe2Id, $holidayId, $holidayInFuture],
			array_map(function($timeframe) {
				return $timeframe->ID;
			}, $allTimeframesForLocAndItem)
		);
	}

	protected function setUp() : void {
	parent::setUp();

	// Timeframe with enddate
	$this->timeframeId = $this->createTimeframe(
		$this->locationId,
		$this->itemId,
		self::REPETITION_START,
		self::REPETITION_END
	);

	// Timeframe without enddate
	$this->timeframe2Id = $this->createTimeframe(
		$this->locationId,
		$this->itemId,
		self::REPETITION_START,
		null
	);
}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
