<?php

namespace CommonsBooking\Tests\API;

/**
 * Abstract test case which implicitly tests REST Routes
 */
class CB_REST_Route_UnitTestCase extends CB_REST_UnitTestCase {

	protected $ENDPOINT;

	public function testRoute() {
		$this->assertNotNull( $this->ENDPOINT );
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->ENDPOINT, $routes );
	}
}