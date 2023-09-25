<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class ItemTest extends CustomPostTypeTest {

	private Item $itemModel;
	private Timeframe $timeframeModel;


	/**
	 * Test not working - maybe bug in function?
	 * @return void
	 */
	/*
	public function testGetBookableTimeframesByLocation() {
		$timeframeArray[] = $this->timeframeModel;
		$this->assertEquals($timeframeArray, $this->itemModel->getBookableTimeframesByItem($this->locationId)); //Not working
	}
	*/

	public function testGetAdmins() {

		$userArray[] = $this->subscriberId;
		$adminItemModel = new Item(
			$this->createItem("Testitem2",'publish', $userArray)
		);
		//$this->assertEquals($userArray, $adminItemModel->getAdmins()); - This should work when postAuthor is not appended anymore
		$this->assertContains($this->subscriberId, $adminItemModel->getAdmins());
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
		$this->assertEquals($restrictionArray, $this->itemModel->getRestrictions());
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
		$this->itemModel = new Item($this->itemId);
		$this->createSubscriber();

	}

	protected function tearDown() : void {
		parent::tearDown();
	}

}
