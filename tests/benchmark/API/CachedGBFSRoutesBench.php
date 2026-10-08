<?php

namespace CommonsBooking\Tests\Benchmark\API;

/**
 * Same as {@see GBFSRoutesBench}, but with CommonsBooking's cache enabled.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @Warmup(2)
 */
class CachedGBFSRoutesBench extends GBFSRoutesBench {

	protected function useCache(): bool {
		return true;
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchVehicleAvailabilityRoute(): void {
		parent::benchVehicleAvailabilityRoute();
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchStationStatusRoute(): void {
		parent::benchStationStatusRoute();
	}

	/**
	 * @Iterations(10)
	 * @Revs(3)
	 */
	public function benchVehicleStatusRoute(): void {
		parent::benchVehicleStatusRoute();
	}
}
