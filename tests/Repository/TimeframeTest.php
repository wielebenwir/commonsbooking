<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TimeframeTest extends CustomPostTypeTest {

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	protected function setUp() : void {
		parent::setUp();

		// Timeframe with enddate
		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);

		// Timeframe without enddate
		$this->createTimeframe(
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
	}

}
