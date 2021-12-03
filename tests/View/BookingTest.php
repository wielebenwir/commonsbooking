<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Booking;
use PHPUnit\Framework\TestCase;

final class BookingTest extends CustomPostTypeTest {

	private $bookingId;

	const USER_ID = 1;

	const BOOKING_ID = 1;

	protected function setUp() {
		parent::setUp();
		$this->createBooking();

	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * Timeframe with enddate.
	 */
	protected function createBooking() {
		// Create Timeframe
		$this->bookingId = wp_insert_post( [
			'post_title'  => 'TestTimeframe',
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
			'post_status' => 'confirmed',
			'post_author' => self::USER_ID
		] );

		update_post_meta( $this->bookingId, 'type', \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID );
		update_post_meta( $this->bookingId, 'timeframe-repetition', 'w' );
		update_post_meta( $this->bookingId, 'start-time', '8:00 AM' );
		update_post_meta( $this->bookingId, 'end-time', '12:00 PM' );
		update_post_meta( $this->bookingId, 'timeframe-max-days', '3' );
		update_post_meta( $this->bookingId, 'location-id', self::BOOKING_ID );
		update_post_meta( $this->bookingId, 'item-id', self::BOOKING_ID );
		update_post_meta( $this->bookingId, 'grid', '0' );
		update_post_meta( $this->bookingId, 'repetition-start', time() - 86400 );
		update_post_meta( $this->bookingId, 'repetition-end', time() + 86400 );
		update_post_meta( $this->bookingId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);
	}

	public function testGetBookingListData() {
		wp_set_current_user( self::USER_ID );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );
	}

}
