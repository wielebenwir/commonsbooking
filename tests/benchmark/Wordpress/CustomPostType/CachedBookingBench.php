<?php

namespace CommonsBooking\Tests\Benchmark\Wordpress\CustomPostType;

/**
 * Same as {@see BookingBench}, but with CommonsBooking's cache enabled.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @Warmup(2)
 */
class CachedBookingBench extends BookingBench {

	protected function useCache(): bool {
		return true;
	}

	/**
	 * @BeforeMethods({"setUp"})
	 * @AfterMethods({"tearDown"})
	 * @Iterations(12)
	 * @Revs(3)
	 */
	public function benchBookingLifecycle(): void {
		parent::benchBookingLifecycle();
	}

	/**
	 * @BeforeMethods({"setUp", "enableBookingRule"})
	 * @AfterMethods({"tearDown"})
	 * @Iterations(12)
	 * @Revs(3)
	 * @ParamProviders({"provideBookingRules"})
	 */
	public function benchBookingLifecycleWithBookingRule( array $params ): void {
		parent::benchBookingLifecycleWithBookingRule( $params );
	}
}
