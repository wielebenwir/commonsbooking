<?php

namespace CommonsBooking\Tests\Benchmark\API;

use CommonsBooking\API\GBFS\StationStatus;
use CommonsBooking\API\GBFS\VehicleAvailability;
use CommonsBooking\API\GBFS\VehicleStatus;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Benchmark\BenchmarkCase;

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class GBFSRoutesBench extends BenchmarkCase {

	/**
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchVehicleAvailabilityRoute(): void {
		rest_do_request( new \WP_REST_Request( 'GET', '/commonsbooking/v1/vehicle_availability.json' ) );
	}

	/**
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchStationStatusRoute(): void {
		rest_do_request( new \WP_REST_Request( 'GET', '/commonsbooking/v1/station_status.json' ) );
	}

	/**
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchVehicleStatusRoute(): void {
		rest_do_request( new \WP_REST_Request( 'GET', '/commonsbooking/v1/vehicle_status.json' ) );
	}

	public function setUp(): void {
		parent::setUp();

		Settings::updateOption( 'commonsbooking_options_api', 'api-activated', 'on' );
		Settings::updateOption( 'commonsbooking_options_api', 'apikey_not_required', 'on' );

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();

		$registerRoutes = static function (): void {
			( new VehicleAvailability() )->register_routes();
			( new StationStatus() )->register_routes();
			( new VehicleStatus() )->register_routes();
		};

		add_action( 'rest_api_init', $registerRoutes );
		do_action( 'rest_api_init' );
		remove_action( 'rest_api_init', $registerRoutes );
	}
}
