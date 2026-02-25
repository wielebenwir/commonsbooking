<?php

namespace CommonsBooking\Tests\API;

class AvailabilityRouteEmptyTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/availability';

	public function testsEmptyAvailabilitySuccess() {

		$request = new \WP_REST_Request( 'GET', $this->ENDPOINT );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, count( $response->get_data()->availability ) );
	}
}
