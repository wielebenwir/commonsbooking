<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;

class DiscoveryRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/gbfs.json';

	public function testRoute() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertNotEmpty( $data->feeds );
		$this->assertIsArray( $data->feeds );

		$header = $response->get_data(); // the top level info that is always present

		$this->assertNotFalse( \DateTime::createFromFormat( \DateTime::ATOM, $header->last_updated ) );
		$this->assertIsInt( $header->ttl );
		$this->assertIsString( $header->version );

		parent::testRoute();
	}
}
