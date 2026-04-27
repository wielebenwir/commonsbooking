<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Tests\CPTCreationTrait;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

/**
 * Abstract test case which implicitly tests REST Routes
 * and provides helpers to create the post types used by all route endpoints.
 */
class CB_REST_Route_UnitTestCase extends CB_REST_UnitTestCase {

	use CPTCreationTrait;

	const CURRENT_DATE = CustomPostTypeTest::CURRENT_DATE;

	protected $ENDPOINT;

	public function testRoute() {
		$this->assertNotNull( $this->ENDPOINT );
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->ENDPOINT, $routes );
	}

	protected function tearDown(): void {
		$this->tearDownAllPosts();
		parent::tearDown();
	}
}
