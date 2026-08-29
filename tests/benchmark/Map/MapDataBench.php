<?php

namespace CommonsBooking\Tests\Benchmark\Map;

use CommonsBooking\Model\Map;
use CommonsBooking\Tests\Benchmark\BenchmarkCase;

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class MapDataBench extends BenchmarkCase {

	private Map $map;

	/**
	 * @Iterations(1)
	 * @Revs(9)
	 */
	public function benchLoadMapData(): void {
		$this->map->get_locations( [] );
	}

	public function setUp(): void {
		parent::setUp();
		$this->map = new Map( $this->createMap() );

		foreach ( $this->locationIds as $locationId ) {
			update_post_meta( $locationId, 'geo_latitude', 50.9413035 );
			update_post_meta( $locationId, 'geo_longitude', 6.9581379 );
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_city', 'Köln' );
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_country', 'Deutschland' );
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_street', 'Domkloster 4' );
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', '50667' );
		}
	}
}
