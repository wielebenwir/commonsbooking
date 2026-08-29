<?php

namespace CommonsBooking\Tests\Benchmark\Wordpress\CustomPostType;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\Benchmark\BenchmarkCase;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingBench extends BenchmarkCase {

	private int $benchmarkItemId;
	private int $benchmarkLocationId;
	private int $repetitionStart;
	private int $repetitionEnd;

	/**
	 * @BeforeMethods({"setUp"})
	 * @AfterMethods({"tearDown"})
	 * @Iterations(1)
	 * @Revs(9)
	 */
	public function benchBookingLifecycle(): void {
		$bookingId         = $this->handleBookingRequest( 'unconfirmed' );
		$this->bookingIds[] = $bookingId;
		$postName           = get_post( $bookingId )->post_name;

		$this->handleBookingRequest( 'confirmed', $bookingId, $postName );
		$this->handleBookingRequest( 'canceled', $bookingId, $postName );
	}

	public function setUp(): void {
		parent::setUp();
		BookingCodes::initBookingCodesTable();
		$this->benchmarkItemId     = $this->createItem( 'Booking Benchmark Item' );
		$this->benchmarkLocationId = $this->createLocation( 'Booking Benchmark Location' );
		$this->repetitionStart     = strtotime( '+1 day midnight' );
		$this->repetitionEnd       = strtotime( '+3 days midnight' ) - 1;

		$timeframeId = $this->createTimeframe(
			$this->benchmarkLocationId,
			$this->benchmarkItemId,
			strtotime( 'today midnight' ),
			strtotime( '+180 days midnight' )
		);
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES, '' );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES, '' );
	}

	private function handleBookingRequest(
		string $status,
		?int $bookingId = null,
		?string $postName = null
	): int {
		return Booking::handleBookingRequest(
			(string) $this->benchmarkItemId,
			(string) $this->benchmarkLocationId,
			$status,
			$bookingId,
			null,
			(string) $this->repetitionStart,
			(string) $this->repetitionEnd,
			$postName,
			null
		);
	}
}
