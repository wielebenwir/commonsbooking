<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;

/**
 * TODO: add result unit test
 */
class SystemInformationRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/system_information.json';

	public function testRoute() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$data = $response->get_data()->data;
		$this->assertNotEmpty( $data->name );
		$this->assertNotEmpty( $data->system_id );

		$header = $response->get_data(); // the top level info that is always present

		$this->assertNotFalse( \DateTime::createFromFormat( \DateTime::ATOM, $header->last_updated ) );
		$this->assertIsInt( $header->ttl );
		$this->assertIsString( $header->version );
		parent::testRoute();
	}

	public function setUp(): void {
		parent::setUp();
	}
}
