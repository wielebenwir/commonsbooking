<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;


class TimeframeTest extends CustomPostTypeTest {

	public $testPostId;

	public function testIsLocked() {
		$timeframe = get_post( $this->testPostId );
		$this->assertTrue( Timeframe::isLocked( $timeframe ) );
	}

	public function testIsOverBookable() {
		$timeframe = get_post( $this->testPostId );
		$this->assertFalse( Timeframe::isOverBookable( $timeframe ) );
	}

	protected function setUp(): void {
		$this->testPostId = wp_insert_post( [
			'post_title'   => 'Booking'
		] );

		// Timeframe is a booking
		update_post_meta( $this->testPostId, 'type', Timeframe::BOOKING_ID );
	}

	protected function tearDown(): void {
		wp_delete_post( $this->testPostId, true );
	}
}
