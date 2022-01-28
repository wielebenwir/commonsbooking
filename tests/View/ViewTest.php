<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\View;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class ViewTest extends CustomPostTypeTest {

	protected function setUp() {
		parent::setUp();

		$now = time();

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+2 days midnight', $now),
			strtotime( '+3 days midnight', $now),
			Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+5 days midnight', $now),
			strtotime( '+6 days midnight', $now),
			Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+8 days midnight', $now),
			strtotime( '+9 days midnight', $now),
			Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+12 days midnight', $now),
			strtotime( '+13 days midnight', $now),
			Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+14 days midnight', $now),
			strtotime( '+15 days midnight', $now),
			Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetShortcodeDataWithFourRangesByItem() {
		$shortCodeData = View::getShortcodeData(new Item($this->itemId), 'Item');
		$this->assertTrue(is_array($shortCodeData[$this->itemId]['ranges']));
		$this->assertTrue(count($shortCodeData[$this->itemId]['ranges']) == 4);
	}

	public function testGetShortcodeDataWithFourRangesByLocation() {
		$shortCodeData = View::getShortcodeData(new Location($this->locationId), 'Location');
		$this->assertTrue(is_array($shortCodeData[$this->locationId]['ranges']));
		$this->assertTrue(count($shortCodeData[$this->locationId]['ranges']) == 4);
	}

}
