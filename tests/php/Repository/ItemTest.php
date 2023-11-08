<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Item;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class ItemTest extends CustomPostTypeTest {

	protected function setUp() : void {
		parent::setUp();

		// Create timeframe with location and item, so that we can search vor it
		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight' ),
			strtotime( '+90 days' )
		);
	}

	public function testGetByLocation() : void {
		$this->assertEquals([$this->itemId],
			array_map(fn($item) => $item->ID,Item::getByLocation($this->locationId, true)
			)
		);
	}

}
