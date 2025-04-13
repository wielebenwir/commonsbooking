<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class LocationTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();

		// Create timeframe with location and item, so that we can search vor it
		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight' ),
			strtotime( '+90 days' )
		);
	}

	public function testGetByItem() {
		$this->assertTrue( count( Location::getByItem( $this->itemId, true ) ) == 1 );
	}
}
