<?php

namespace CommonsBooking\Tests\Benchmark\View;

/**
 * Same as {@see ShortcodeBench}, but with CommonsBooking's cache enabled.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @Warmup(2)
 */
class CachedShortcodeBench extends ShortcodeBench {

	protected function useCache(): bool {
		return true;
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchItemsShortcode(): void {
		parent::benchItemsShortcode();
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchLocationsShortcode(): void {
		parent::benchLocationsShortcode();
	}
}
