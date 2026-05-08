<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use SlopeIt\ClockMock\ClockMock;

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

	public function testOpeningHours() {
		// no timeframe setup, meaning no items to book -> closed
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data()->data;
		$this->assertEquals( '24/7 closed', $data->opening_hours );

		// with timeframe, is open
		$this->createBookableTimeFrameIncludingCurrentDay();
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data()->data;
		$this->assertEquals( '24/7', $data->opening_hours );

		// outside of the timeframe, closed again
		$inAWeek = new \DateTime( self::CURRENT_DATE );
		$inAWeek->modify( '+1 week' );
		ClockMock::freeze( $inAWeek );
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data()->data;
		$this->assertEquals( '24/7 closed', $data->opening_hours );
	}

	public function setUp(): void {
		parent::setUp();
	}
}
