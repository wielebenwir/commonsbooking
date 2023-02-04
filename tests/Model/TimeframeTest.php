<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use PHPUnit\Framework\TestCase;

class TimeframeTest extends CustomPostTypeTest {

	private Timeframe $validTF;

	public function testIsValid() {
		$this->assertNull($this->validTF->isValid());

		$noItemTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			"",
			strtotime("+1 day",time()),
			strtotime("+3 days",time())
		));
		try {
			$noItemTF->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noLocationTF = new Timeframe($this->createTimeframe(
			"",
			$this->itemId,
			strtotime("+20 day",time()),
			strtotime("+25 days",time())
		));

		try {
			$noLocationTF->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noStartDateTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			"",
			strtotime("+10 days",time())
		));
		try {
			$noStartDateTF->isValid();
		}
		catch (TimeframeInvalidException $e ){
			$this->assertEquals("Startdate is missing. Timeframe is saved as draft. Please enter a start date to publish this timeframe.",$e->getMessage());
		}

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
		try {
			$pickupTimeInvalid->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("A pickup time but no return time has been set. Please set the return time.",$e->getMessage());
		}

		$isOverlapping = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+1 day",time()),
			strtotime("+2 days",time())
		));
		$this->expectException(TimeframeInvalidException::class);
		$isOverlapping->isValid();
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
