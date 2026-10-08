<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Tests\Benchmark\BenchmarkCase;
use CommonsBooking\View\Calendar;

use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

/**
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class CalendarBench extends BenchmarkCase {

	const ITEMS_DISCONNECTED     = 20; // items without a timeframe, see #2084
	const LOCATIONS_DISCONNECTED = 20; // locations without a timeframe, see #2084
	const USER_ID                = 1; // The user that owns all of those bookings


	/**
	 * @Iterations(3)
	 * @Revs(3)
	 * @return void
	 * @throws \Exception
	 */
	public function benchRenderTable() {
		$calendar = Calendar::renderTable( [] );
		assertStringNotContainsString( 'No items found.', $calendar );
		assertStringContainsString( 'is-booked', $calendar ); // assert that at least some items appear booked
	}
}
