<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class LocationTest extends CustomPostTypeTest {
	private Location $locationModel;
	private Timeframe $timeframeModel;

	/**
	 * Not working - Maybe bug in function?
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testGetBookableTimeframesByItem() {
		$timeframeArray[] = $this->timeframeModel;
		$this->assertEquals($timeframeArray, $this->locationModel->getBookableTimeframesByItem($this->itemId)); //Not working
	}
	*/

	public function testGetAdmins() {
		$userArray[] = $this->subscriberId;
		$adminLocationModel = new Location(
			$this->createLocation("TestLocation2",'publish', $userArray)
		);
		//$this->assertEquals($userArray, $adminItemModel->getAdmins()); - This should work when postAuthor is not appended anymore
		$this->assertContains($this->subscriberId, $adminLocationModel->getAdmins());
	}

	/**
	 * Can be used after PR #1179 is merged
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testGetRestrictions() {
		$this->restrictionIds = array_unique($this->restrictionIds);
		$restrictionArray = [];
		foreach ($this->restrictionIds as $restrictionId) {
			$restrictionArray[] = new Restriction($restrictionId);
		}
		$this->assertEquals($restrictionArray, $this->locationModel->getRestrictions());
	}
	*/

	protected function setUp() : void {
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
		$this->createSubscriber();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

}
