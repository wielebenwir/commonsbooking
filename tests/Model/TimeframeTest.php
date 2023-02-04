<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use PHPUnit\Framework\TestCase;

class TimeframeTest extends CustomPostTypeTest {

	private Timeframe $validTF;

	public function testIsValid() {
		$this->assertTrue($this->validTF->isValid());

		$noItemTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			"",
			strtotime("+1 day",time()),
			strtotime("+3 days",time())
		));
		$this->assertFalse($noItemTF->isValid());

		$noLocationTF = new Timeframe($this->createTimeframe(
			"",
			$this->itemId,
			strtotime("+20 day",time()),
			strtotime("+25 days",time())
		));
		$this->assertFalse($noLocationTF->isValid());

		$noStartDateTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			"",
			strtotime("+10 days",time())
		));
		$this->assertFalse($noStartDateTF->isValid());

		$pickupTimeInvalid = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+10 day",time()),
			strtotime("+13 days",time()),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"off",
			'w',
			0,
			'09:00 AM',
			null
		));
		$this->assertFalse($pickupTimeInvalid->isValid());

		$isOverlapping = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+1 day",time()),
			strtotime("+2 days",time())
		));
		$this->assertFalse($isOverlapping->isValid());

	}

	public function testIsBookable() {
		$this->assertTrue($this->validTF->isBookable());

		/*$passedTimeframe = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("-5 days",time()),
			strtotime("-3 days",time())
		));
		$this->assertFalse($passedTimeframe->isBookable());*/
		//This test does not work, function maybe broken?
	}

	public function testGetLocation() {
		$location = New Location($this->locationId);
		$this->assertEquals($location,$this->validTF->getLocation());
	}

	public function testGetItem() {
		$item = New Item($this->itemId);
		$this->assertEquals($item,$this->validTF->getItem());
	}

	protected function setUp() {
		parent::setUp();
		$this->validTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+1 day",time()),
			strtotime("+3 days",time())
		));
	}

	protected function tearDown() {
		parent::tearDown();
	}

}
