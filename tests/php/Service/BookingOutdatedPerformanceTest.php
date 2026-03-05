<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Repository\Booking as BookingRepository;
use CommonsBooking\Service\Booking as BookingService;
use CommonsBooking\Wordpress\CustomPostType\Booking as BookingCPT;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

/**
 * Performance tests for the cb-outdated marking pipeline.
 *
 * Extends BookingOutdatedScaleTest to reuse createPastLinearBookings().
 * Each test measures and prints wall-clock time for:
 *   - booking creation
 *   - markOutdatedBookings() (looped until all past bookings are covered,
 *     because the method processes at most 500 per call)
 *   - getByTimerange() on the full past range (the key query we optimise)
 *
 * Run these with:
 *   php8.1 vendor/bin/phpunit --bootstrap tests/php/bootstrap.php \
 *       tests/php/Service/BookingOutdatedPerformanceTest.php
 *
 * Each test is annotated @large so PHPUnit enforces the 120-second
 * timeoutForLargeTests limit configured in phpunit.xml.dist.
 * A test exceeding that budget fails with a PHPUnit\Framework\RiskyTestError.
 *
 * @group slow
 * @large
 */
class BookingOutdatedPerformanceTest extends BookingOutdatedScaleTest {

	// -----------------------------------------------------------------------
	// Internal timing log — printed once per test in tearDown
	// -----------------------------------------------------------------------

	/** @var array<string, float> */
	private array $timing = [];

	private int $scenarioCount = 0;

	protected function tearDown(): void {
		if ( $this->timing ) {
			$this->printReport();
		}
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Timing helpers
	// -----------------------------------------------------------------------

	private function tick(): float {
		return microtime( true );
	}

	private function elapsed( float $since ): float {
		return microtime( true ) - $since;
	}

	private function record( string $label, float $seconds ): void {
		$this->timing[ $label ] = $seconds;
	}

	private function printReport(): void {
		$n    = $this->scenarioCount;
		$rows = [
			[ 'Phase',                   'Time (s)',           'ms / booking'                                        ],
			[ str_repeat( '─', 30 ),     str_repeat( '─', 10 ), str_repeat( '─', 14 )                               ],
			[ "Create $n bookings",      $this->timing['create'] ?? 0,
				( $n > 0 ) ? ( ( $this->timing['create'] ?? 0 ) / $n * 1000 ) : 0                                    ],
			[ 'markOutdatedBookings()',   $this->timing['mark'] ?? 0,
				( $n > 0 ) ? ( ( $this->timing['mark'] ?? 0 ) / $n * 1000 ) : 0                                      ],
			[ 'Batches (×500)',           $this->timing['batches'] ?? 0,     ''                                       ],
			[ 'getByTimerange()',         $this->timing['query'] ?? 0,       ''                                       ],
			[ 'getExistingBookings()',    $this->timing['existing'] ?? 0,    ''                                       ],
			[ str_repeat( '─', 30 ),     str_repeat( '─', 10 ),             str_repeat( '─', 14 )                    ],
			[ 'Total',                   $this->timing['total'] ?? 0,        ''                                       ],
		];

		$out  = PHP_EOL;
		$out .= sprintf( '  ┌ BookingOutdatedPerformanceTest  N = %d ┐' . PHP_EOL, $n );
		foreach ( $rows as $row ) {
			$label   = str_pad( (string) $row[0], 30 );
			$seconds = is_float( $row[1] ) ? sprintf( '%8.3f', $row[1] ) : str_pad( (string) $row[1], 8 );
			$msPerB  = is_float( $row[2] ?? '' ) ? sprintf( '%10.2f', $row[2] ) : str_pad( (string) ( $row[2] ?? '' ), 10 );
			$out    .= sprintf( '  │ %s  %s  %s │' . PHP_EOL, $label, $seconds, $msPerB );
		}
		$out .= '  └' . str_repeat( '─', strlen( '  ┌ BookingOutdatedPerformanceTest  N = 200 ┐' ) - 4 ) . '┘' . PHP_EOL;
		$out .= PHP_EOL;

		fwrite( STDOUT, $out );
	}

	// -----------------------------------------------------------------------
	// Bulk-SQL booking creation (bypasses WP hooks for speed)
	// -----------------------------------------------------------------------

	/**
	 * Overrides the parent implementation with a direct-SQL version that is
	 * ~30× faster than wp_insert_post() + update_post_meta().
	 *
	 * Only the five meta fields that markOutdatedBookings() and the two
	 * repository queries actually need are written:
	 *   type, repetition-start, repetition-end, location-id, item-id
	 *
	 * @return array<int, array{id: int, start: int, end: int}>
	 */
	protected function createPastLinearBookings( int $count ): array {
		global $wpdb;

		$midnight   = strtotime( 'midnight' );
		$now        = current_time( 'mysql' );
		$slots      = [];
		$metaRows   = [];

		for ( $i = $count; $i >= 1; $i-- ) {
			$dayStart = $midnight - $i * DAY_IN_SECONDS;
			$dayEnd   = $midnight - ( $i - 1 ) * DAY_IN_SECONDS - 1;

			// Insert the post row directly — no hooks, no cache invalidation.
			$wpdb->insert(
				$wpdb->posts,
				[
					'post_title'        => 'Perf Booking',
					'post_type'         => BookingCPT::$postType,
					'post_status'       => 'confirmed',
					'post_author'       => \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::USER_ID,
					'post_date'         => $now,
					'post_date_gmt'     => $now,
					'post_modified'     => $now,
					'post_modified_gmt' => $now,
					'post_name'         => '',
					'to_ping'           => '',
					'pinged'            => '',
					'post_content_filtered' => '',
					'post_excerpt'      => '',
					'post_content'      => '',
					'guid'              => '',
					'comment_status'    => 'closed',
					'ping_status'       => 'closed',
				],
				[
					'%s', '%s', '%s', '%d',
					'%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s',
					'%s', '%s',
				]
			);

			$id = $wpdb->insert_id;

			// Track so that parent tearDown can clean them up via $this->bookingIds.
			$this->bookingIds[] = $id;

			$slots[]    = [ 'id' => $id, 'start' => $dayStart, 'end' => $dayEnd ];
			$metaRows[] = [ $id, 'type',               Timeframe::BOOKING_ID ];
			$metaRows[] = [ $id, 'repetition-start',   $dayStart             ];
			$metaRows[] = [ $id, 'repetition-end',     $dayEnd               ];
			$metaRows[] = [ $id, 'location-id',        $this->locationId     ];
			$metaRows[] = [ $id, 'item-id',            $this->itemId         ];
		}

		// Single batch INSERT for all postmeta rows.
		if ( $metaRows ) {
			$placeholders = implode(
				', ',
				array_fill( 0, count( $metaRows ), '(%d, %s, %s)' )
			);
			$flat = [];
			foreach ( $metaRows as $r ) {
				$flat[] = $r[0];
				$flat[] = $r[1];
				$flat[] = $r[2];
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES $placeholders", $flat ) );
		}

		return $slots;
	}

	// -----------------------------------------------------------------------
	// Timed scenario
	// -----------------------------------------------------------------------

	/**
	 * Runs the full outdated-marking pipeline with granular timing.
	 *
	 * markOutdatedBookings() processes at most 500 bookings per call, so for
	 * N > 500 we loop ceil(N/500) times to ensure full coverage.
	 *
	 * Correctness is verified by spot-checking the first, middle, and last
	 * booking IDs (checking all N would itself become a performance bottleneck).
	 * The definitive correctness assertion is that getByTimerange() returns
	 * empty after marking — that is the query we are optimising.
	 */
	protected function runTimedScenario( int $count ): void {
		$this->scenarioCount = $count;
		$this->timing        = [];
		$tTotal              = $this->tick();

		$midnight = strtotime( 'midnight' );

		// ── 1. Create past bookings ──────────────────────────────────────────
		$t        = $this->tick();
		$slots    = $this->createPastLinearBookings( $count );
		$pastIds  = array_column( $slots, 'id' );
		$this->record( 'create', $this->elapsed( $t ) );

		// ── 2. Future control booking ────────────────────────────────────────
		$futureId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			$midnight + DAY_IN_SECONDS,
			$midnight + 2 * DAY_IN_SECONDS,
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);

		// ── 3. markOutdatedBookings() — loop until all past slots covered ────
		$batches      = (int) ceil( $count / 500 );
		$t            = $this->tick();
		for ( $i = 0; $i < $batches; $i++ ) {
			BookingService::markOutdatedBookings();
		}
		$this->record( 'mark',    $this->elapsed( $t ) );
		$this->record( 'batches', $batches );

		// ── 4. Spot-check correctness (first / middle / last) ────────────────
		$probeIndices = array_unique( [ 0, (int) ( $count / 2 ), $count - 1 ] );
		foreach ( $probeIndices as $i ) {
			$this->assertEquals(
				'cb-outdated',
				get_post( $pastIds[ $i ] )->post_status,
				"Booking at index $i (ID {$pastIds[$i]}) should be cb-outdated"
			);
		}
		$this->assertEquals( 'confirmed', get_post( $futureId )->post_status, 'Future booking must remain confirmed' );

		// ── 5. Query timing — the key perf measurement ───────────────────────
		$rangeStart = $slots[0]['start'];
		$rangeEnd   = $slots[ $count - 1 ]['end'];

		$t           = $this->tick();
		$byTimerange = BookingRepository::getByTimerange( $rangeStart, $rangeEnd, $this->locationId, $this->itemId );
		$this->record( 'query', $this->elapsed( $t ) );
		$this->assertEmpty( $byTimerange, "getByTimerange must not return any of the $count cb-outdated bookings" );

		$t        = $this->tick();
		$existing = BookingRepository::getExistingBookings( $this->itemId, $this->locationId, $rangeStart, $rangeEnd );
		$this->record( 'existing', $this->elapsed( $t ) );
		$this->assertEmpty( $existing, "getExistingBookings must not return any of the $count cb-outdated bookings" );

		$this->record( 'total', $this->elapsed( $tTotal ) );
	}

	// -----------------------------------------------------------------------
	// Test cases
	// -----------------------------------------------------------------------

	public function testWith2000PastBookings(): void {
		$this->runTimedScenario( 2000 );
	}

	public function testWith5000PastBookings(): void {
		$this->runTimedScenario( 5000 );
	}

	public function testWith6000PastBookings(): void {
		$this->runTimedScenario( 6000 );
	}

	public function testWith7000PastBookings(): void {
		$this->runTimedScenario( 7000 );
	}

}
