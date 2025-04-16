<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\CB1;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class CB1Test extends CustomPostTypeTest {

	private $timeframeInstanceId;


	protected function setUp(): void {
		parent::setUp();

		$this->timeframeInstanceId = parent::createBookableTimeFrameIncludingCurrentDay();

		// Setup CB 2 Timeframe with CB 1 ID == 1
		update_post_meta( $this->timeframeInstanceId, '_cb_cb1_post_post_ID', 1 );

		// Setup CB 2 Location with CB 1 ID == 2
		update_post_meta( $this->locationId, '_cb_cb1_post_post_ID', 2 );

		// Setup CB 2 Item with CB 1 ID == 3
		update_post_meta( $this->itemId, '_cb_cb1_post_post_ID', 3 );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetCB2TimeframeId() {
		$this->assertTrue( CB1::getCB2TimeframeId( 1 ) == $this->timeframeInstanceId );
	}

	public function testGetCB2LocationId() {
		$this->assertTrue( CB1::getCB2LocationId( 2 ) == $this->locationId );
	}

	public function testGetCB2PostIdByCB1Id() {
		$this->assertTrue( CB1::getCB2PostIdByCB1Id( 1 ) == $this->timeframeInstanceId );
		$this->assertTrue( CB1::getCB2PostIdByCB1Id( 2 ) == $this->locationId );
		$this->assertTrue( CB1::getCB2PostIdByCB1Id( 3 ) == $this->itemId );
	}

	public function testGetCB2ItemId() {
		$this->assertTrue( CB1::getCB2ItemId( 3 ) == $this->itemId );
	}
}
