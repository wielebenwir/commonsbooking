<?php

namespace CommonsBooking\Tests\Benchmark\Map;

use CommonsBooking\Map\MapData;
use CommonsBooking\Map\MapItemAvailable;
use CommonsBooking\Model\Map;
use CommonsBooking\Tests\Benchmark\BenchmarkCase;

use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertTrue;

/**
 * Benchmarks the AJAX endpoint that feeds the frontend map.
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class MapDataBench extends BenchmarkCase {

	private Map $map;

	private int $mapId;

	private string $availabilityDateStart;

	private string $availabilityDateEnd;

	/**
	 * Baseline: only location and item data
	 *
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchLoadMapData(): void {
		$this->map->get_locations( [] );
	}

	/**
	 * Same data set as {@see self::benchLoadMapData()}, but with the item availability
	 * of the map popup / filter enabled (@see MapData::get_locations()).
	 *
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchLoadMapDataWithAvailability(): void {
		$locations = $this->map->get_locations( [] );

		$locations = MapItemAvailable::create_items_availabilities(
			$locations,
			$this->availabilityDateStart,
			$this->availabilityDateEnd
		);

		$locations = Map::cleanup_location_data( array_values( $locations ), '<br>' );

		// Make sure the benchmark does not silently measure a no-op: the fixture
		// timeframes must be found and at least one day must be mapped to a status.
		assertNotEmpty( $locations );
		$firstLocation = reset( $locations );
		assertNotEmpty( $firstLocation['items'] );
		$statuses = array_column( $firstLocation['items'][0]['availability'], 'status' );
		assertNotEmpty( $statuses );
		assertNotEmpty( array_diff( array_unique( $statuses ), [ MapItemAvailable::OUT_OF_TIMEFRAME ] ) );
	}

	public function setUp(): void {
		parent::setUp();
		$this->mapId = $this->createMap();
		$this->map   = new Map( $this->mapId );

		// Enables the availability calculation in the map frontend.
		update_post_meta( $this->mapId, 'show_item_availability', 'on' );

		$settings                    = MapData::get_settings( $this->mapId );
		$this->availabilityDateStart = $settings['filter_availability']['date_min'];
		$this->availabilityDateEnd   = $settings['filter_availability']['date_max'];

		assertTrue( $settings['show_item_availability'] );

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
