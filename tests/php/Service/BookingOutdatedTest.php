<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Plugin;
use CommonsBooking\Service\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class BookingOutdatedTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}

	public function testMarkOutdated_marksConfirmedPastBooking() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);

		Booking::markOutdatedBookings();

		$post = get_post( $bookingId );
		$this->assertEquals( 'cb-outdated', $post->post_status );
	}

	public function testMarkOutdated_doesNotMarkFutureBooking() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day' ),
			strtotime( '+5 days' ),
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);

		Booking::markOutdatedBookings();

		$post = get_post( $bookingId );
		$this->assertEquals( 'confirmed', $post->post_status );
	}

	public function testMarkOutdated_doesNotMarkCanceledBooking() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'canceled'
		);

		Booking::markOutdatedBookings();

		$post = get_post( $bookingId );
		$this->assertEquals( 'canceled', $post->post_status );
	}

	public function testOutdatedExcludedFromExistingBookings() {
		$start = strtotime( '-3 days' );
		$end   = strtotime( '-2 days' );

		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$start,
			$end,
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);

		Booking::markOutdatedBookings();

		$existing = \CommonsBooking\Repository\Booking::getExistingBookings(
			$this->itemId,
			$this->locationId,
			$start,
			$end
		);

		$ids = array_map( fn( $b ) => $b->ID, $existing );
		$this->assertNotContains( $bookingId, $ids );
	}

	public function testMaybeResetOutdatedBooking_resetsWhenEndInFuture() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		// Extend end date into the future
		update_post_meta( $bookingId, \CommonsBooking\Model\Timeframe::REPETITION_END, strtotime( '+3 days' ) );

		Plugin::maybeResetOutdatedBooking( $bookingId );

		$post = get_post( $bookingId );
		$this->assertEquals( 'confirmed', $post->post_status );
	}

	public function testMaybeResetOutdatedBooking_doesNotResetWhenEndInPast() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-3 days' ),
			strtotime( '-2 days' ),
			'8:00 AM',
			'12:00 PM',
			'cb-outdated'
		);

		Plugin::maybeResetOutdatedBooking( $bookingId );

		$post = get_post( $bookingId );
		$this->assertEquals( 'cb-outdated', $post->post_status );
	}
}
