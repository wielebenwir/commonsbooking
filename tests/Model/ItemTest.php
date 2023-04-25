<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class ItemTest extends CustomPostTypeTest {

	private $itemModel;
	private $timeframeModel;

	public function testGetRestrictions() {
		$this->restrictionIds = array_unique($this->restrictionIds);
		$restrictionArray = [];
		foreach ($this->restrictionIds as $restrictionId) {
			$restrictionArray[] = new Restriction($restrictionId);
		}
		$this->assertEquals($restrictionArray, $this->itemModel->getRestrictions());
	}

	protected function setUp() : void {
		parent::setUp();
		$this->restrictionIds[] = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			NULL
		);
		$this->timeframeModel   = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		$this->itemModel        = new Item( $this->itemId );
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

}
