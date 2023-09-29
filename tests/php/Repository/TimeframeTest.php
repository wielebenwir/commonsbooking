<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TimeframeTest extends CustomPostTypeTest {

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	protected int $timeframeId;
	protected int $timeframe2Id;

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
}
