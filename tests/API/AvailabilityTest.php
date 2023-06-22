<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;

class AvailabilityTest extends \WP_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/availability';

	protected $server;

	/**
	 * TODO move to abstract api test router
	 */
	public function setUp() : void {
		parent::setUp();
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;

		// Enables api
		Settings::updateOption('commonsbooking_options_api', 'api-activated', 'on');
		Settings::updateOption('commonsbooking_options_api', 'apikey_not_required', 'on');

		// Registers routes (via rest_api_init hook)
		( new Plugin() )->initRoutes();

		// Applies hook
		do_action( 'rest_api_init' );


	}

	/**
	 * TODO remove after initial impl
	 */
	public function testRoute() {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/posts' );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	// TODO move to abstract test case
	public function testRoutes() {
		$routes = $this->server->get_routes();
		//foreach (array_keys($routes) as $key) {
		//	echo( $key . "\n");
		//}
		$this->assertArrayHasKey( $this->ENDPOINT, $routes );
	}

	public function testsAvailabilitySuccess() {

	 	$request = new \WP_REST_Request( 'GET', $this->ENDPOINT );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
	}
}
