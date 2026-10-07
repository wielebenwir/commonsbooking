<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Tests\Benchmark\BenchmarkCase;
use CommonsBooking\View\Item;
use CommonsBooking\View\Location;

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class ShortcodeBench extends BenchmarkCase {

	/**
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchItemsShortcode(): void {
		Item::shortcode( [] );
	}

	/**
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchLocationsShortcode(): void {
		Location::shortcode( [] );
	}
}
