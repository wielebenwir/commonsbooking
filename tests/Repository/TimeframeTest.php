<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use PHPUnit\Framework\TestCase;

class TimeframeTest extends CustomPostTypeTest {

	protected function setUp() {
		parent::setUp();
		$this->createBookableTimeFrameWithEnddate();
		$this->createBookableTimeFrameWithoutEnddate();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetInRange() {
		$inRangeTimeFrames = Timeframe::getInRange(self::REPETITION_START, self::REPETITION_END);
		$this->assertTrue(count($inRangeTimeFrames) == 2);
	}

}
