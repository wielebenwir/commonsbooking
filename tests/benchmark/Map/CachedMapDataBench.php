<?php

namespace CommonsBooking\Tests\Benchmark\Map;

/**
 * Same as {@see MapDataBench}, but with CommonsBooking's cache enabled.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @Warmup(2)
 */
class CachedMapDataBench extends MapDataBench {

	protected function useCache(): bool {
		return true;
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchLoadMapData(): void {
		parent::benchLoadMapData();
	}
}
