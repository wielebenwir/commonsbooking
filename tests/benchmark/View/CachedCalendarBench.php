<?php

namespace CommonsBooking\Tests\Benchmark\View;

/**
 * Same as {@see CalendarBench}, but with CommonsBooking's cache enabled.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @Warmup(2)
 */
class CachedCalendarBench extends CalendarBench {

	protected function useCache(): bool {
		return true;
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 * @return void
	 * @throws \Exception
	 */
	public function benchRenderTable() {
		parent::benchRenderTable();
	}
}
