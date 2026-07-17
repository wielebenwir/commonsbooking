<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;

class VehicleTypesRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/vehicle_types.json';
	public function testRoute() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;

		$this->assertCount( 1, $data->vehicle_types ); // just one vehicle type with the default settings

		parent::testRoute();
	}
}
