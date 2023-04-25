<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class LocationTest extends CustomPostTypeTest {

	private Location $locationModel;
	private Timeframe $timeframeModel;

	public function testGetRestrictions() {
		$this->restrictionIds = array_unique($this->restrictionIds);
		$restrictionArray = [];
		foreach ($this->restrictionIds as $restrictionId) {
			$restrictionArray[] = new Restriction($restrictionId);
		}
		$this->assertEquals($restrictionArray, $this->locationModel->getRestrictions());
	}

	protected function setUp() {
		parent::setUp();
		$this->restrictionIds[] = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			null
		);
		$this->timeframeModel = new Timeframe($this->createBookableTimeFrameIncludingCurrentDay());
		$this->locationModel = new Location($this->locationId);
	}

	protected function tearDown() {
		parent::tearDown();
	}

}
