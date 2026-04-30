<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Repository\Booking as BookingRepository;
use CommonsBooking\Service\Booking as BookingService;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

/**
 * Verifies that cb-outdated correctly excludes past bookings from all relevant
 * repository methods at scale.
 *
 * A helper creates N confirmed bookings linearly in the past (one per day,
 * from $count days ago up to yesterday). After markOutdatedBookings() runs:
 *   - Every past booking must be cb-outdated
 *   - A control future booking must remain confirmed
 *   - All key repository queries must return empty / must not contain the
 *     outdated booking IDs
 *
 * The same scenario is exercised at three scales: 2, 20, and 200 bookings.
 */
class BookingOutdatedScaleTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}

	// -----------------------------------------------------------------------
	// Helper
	// -----------------------------------------------------------------------

	/**
	 * Creates $count confirmed bookings in the past, each spanning exactly one
	 * calendar day. The sequence runs from ($count days ago) up to and
	 * including yesterday:
	 *
	 *   slot[0]  : midnight(-N)   …  midnight(-N+1) - 1
	 *   slot[1]  : midnight(-N+1) …  midnight(-N+2) - 1
	 *   …
	 *   slot[N-1]: midnight(-1)   …  midnight      - 1   (= yesterday)
	 *
	 * All repetition-end values are < today's midnight, so every booking is
	 * a candidate for markOutdatedBookings().
	 *
	 * @return array<int, array{id: int, start: int, end: int}>
	 */
	protected function createPastLinearBookings( int $count ): array {
		$midnight = strtotime( 'midnight' );
		$slots    = [];

		for ( $i = $count; $i >= 1; $i-- ) {
			$dayStart = $midnight - $i * DAY_IN_SECONDS;
			$dayEnd   = $midnight - ( $i - 1 ) * DAY_IN_SECONDS - 1;

			$id = $this->createBooking(
				$this->locationId,
				$this->itemId,
				$dayStart,
				$dayEnd,
				'8:00 AM',
				'12:00 PM',
				'confirmed'
			);

			$slots[] = [ 'id' => $id, 'start' => $dayStart, 'end' => $dayEnd ];
		}

		return $slots;
	}

	/**
	 * Core scenario executed at each scale.
	 *
	 * Steps:
	 *  1. Create $count linearly-spaced past confirmed bookings.
	 *  2. Create one future confirmed booking as a control.
	 *  3. Assert all past bookings are 'confirmed' (sanity check).
	 *  4. Run markOutdatedBookings().
	 *  5. Assert every past booking is now 'cb-outdated'.
	 *  6. Assert the future booking is still 'confirmed'.
	 *  7. Assert cb-outdated bookings are absent from:
	 *       - getByTimerange()
	 *       - getExistingBookings()
	 *       - getEndingBookingsByDate()
	 *       - getBeginningBookingsByDate()
	 */
	protected function runScenario( int $count ): void {
		$midnight = strtotime( 'midnight' );

		// 1. Past bookings
		$slots   = $this->createPastLinearBookings( $count );
		$pastIds = array_column( $slots, 'id' );

		// 2. Future control booking (tomorrow → day after tomorrow)
		$futureId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$midnight + DAY_IN_SECONDS,
			$midnight + 2 * DAY_IN_SECONDS,
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);

		// 3. Sanity: all past bookings start as confirmed
		foreach ( $pastIds as $id ) {
			$this->assertEquals( 'confirmed', get_post( $id )->post_status, "Booking #$id should be confirmed before cron" );
		}

		// 4. Run the cron method
		BookingService::markOutdatedBookings();

		// 5. Every past booking must now be cb-outdated
		foreach ( $pastIds as $id ) {
			$this->assertEquals( 'cb-outdated', get_post( $id )->post_status, "Booking #$id should be cb-outdated after cron" );
		}

		// 6. Future booking must be unaffected
		$this->assertEquals( 'confirmed', get_post( $futureId )->post_status, 'Future booking must remain confirmed' );

		$rangeStart = $slots[0]['start'];
		$rangeEnd   = $slots[ $count - 1 ]['end'];

		// 7a. getByTimerange — covers the full past range
		$byTimerange = BookingRepository::getByTimerange(
			$rangeStart,
			$rangeEnd,
			$this->locationId,
			$this->itemId
		);
		$this->assertEmpty( $byTimerange, "getByTimerange must not return any of the $count cb-outdated bookings" );

		// 7b. getExistingBookings — same range
		$existing = BookingRepository::getExistingBookings(
			$this->itemId,
			$this->locationId,
			$rangeStart,
			$rangeEnd
		);
		$this->assertEmpty( $existing, "getExistingBookings must not return any of the $count cb-outdated bookings" );

		// 7c. getEndingBookingsByDate — last past day
		$lastDayTs   = $slots[ $count - 1 ]['start']; // midnight of yesterday
		$endingIds   = array_map( fn( $b ) => $b->ID, BookingRepository::getEndingBookingsByDate( $lastDayTs ) );
		$this->assertNotContains(
			$slots[ $count - 1 ]['id'],
			$endingIds,
			'getEndingBookingsByDate must not return the last cb-outdated booking'
		);

		// 7d. getBeginningBookingsByDate — first past day
		$firstDayTs    = $slots[0]['start']; // midnight of $count days ago
		$beginningIds  = array_map( fn( $b ) => $b->ID, BookingRepository::getBeginningBookingsByDate( $firstDayTs ) );
		$this->assertNotContains(
			$slots[0]['id'],
			$beginningIds,
			'getBeginningBookingsByDate must not return the first cb-outdated booking'
		);
	}

	// -----------------------------------------------------------------------
	// Tests at increasing scale
	// -----------------------------------------------------------------------

	public function testWith2PastBookings(): void {
		$this->runScenario( 2 );
	}

	public function testWith20PastBookings(): void {
		$this->runScenario( 20 );
	}

	public function testWith200PastBookings(): void {
		$this->runScenario( 200 );
	}
}
