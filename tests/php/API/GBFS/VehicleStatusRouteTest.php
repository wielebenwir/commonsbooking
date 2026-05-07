<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use SlopeIt\ClockMock\ClockMock;

class VehicleStatusRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/vehicle_status.json';
	private $start;
	private $end;
	private $timeframe;

	public function testIsReserved() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;

		$this->assertFalse( $data->vehicles[0]->is_reserved );

		$this->createConfirmedBookingStartingToday();
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertTrue( $data->vehicles[0]->is_reserved );
	}

	public function testIsDisabled() {
		// base case, not disabled
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );

		// timeframe expired: is disabled
		$future = $this->end->modify( '+1 day' );
		ClockMock::freeze( $future );
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertTrue( $data->vehicles[0]->is_disabled );
	}

	public function setUp(): void {
		parent::setUp();

		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$this->locationId = $this->createLocation( 'Testlocation', 'publish', [] );
		$this->itemId     = $this->createItem( 'TestItem', 'publish' );

		$mocked      = new \DateTimeImmutable( self::CURRENT_DATE );
		$this->start = $mocked->modify( '-1 days' );
		$this->end   = $mocked->modify( '+1 days' );

		$this->timeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->start->getTimestamp(),
			$this->end->getTimestamp()
		);
	}

	public function tearDown(): void {
		ClockMock::reset();
		parent::tearDown();
	}
}
