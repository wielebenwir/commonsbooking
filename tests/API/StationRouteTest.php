<?php

namespace CommonsBooking\Tests\API;

use SlopeIt\ClockMock\ClockMock;

class StationRouteTest extends RouteTest
{
	protected $ENDPOINT = '/commonsbooking/v1/station';

	public function testsAvailabilitySuccess() {

		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$request = new \WP_REST_Request( 'GET', $this->ENDPOINT );

		$response = rest_do_request( $request );

		$this->assertSame( 1, count( $response->get_data() ) );

		// Checks availability for the first day
		$this->assertEquals( $this->locationId, $response->get_data()->availability[0]->locationId );
		$this->assertEquals( $this->itemId, $response->get_data()->availability[0]->itemId );
		$this->assertEquals( self::CURRENT_DATE . 'T00:00:00+00:00', $response->get_data()->availability[0]->start );
		$this->assertEquals( self::CURRENT_DATE . 'T23:59:59+00:00', $response->get_data()->availability[0]->end );

		ClockMock::reset();
	}


}