<?php

namespace CommonsBooking\Tests\API;

use SlopeIt\ClockMock\ClockMock;

class AvailabilityRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/availability';

	private $locationId;
	private $itemId;

	public function setUp(): void {
		parent::setUp();

		// TODO creates initial data (should be mocked in the future)
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$this->locationId = $this->createLocation( 'Testlocation', 'publish' );
		$this->itemId     = $this->createItem( 'TestItem', 'publish' );

		$mocked = new \DateTimeImmutable( self::CURRENT_DATE );

		$start = $mocked->modify( '-1 days' );
		$end   = $mocked->modify( '+1 days' );

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$start->getTimestamp(),
			$end->getTimestamp()
		);

		ClockMock::reset();
	}

	public function testsAvailabilitySuccess() {

		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$request = new \WP_REST_Request( 'GET', $this->ENDPOINT );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 2, count( $response->get_data()->availability ) );

		// Checks availability for the first day
		$this->assertEquals( $this->locationId, $response->get_data()->availability[0]->locationId );
		$this->assertEquals( $this->itemId, $response->get_data()->availability[0]->itemId );
		$this->assertEquals( self::CURRENT_DATE . 'T00:00:00+00:00', $response->get_data()->availability[0]->start );
		$this->assertEquals( self::CURRENT_DATE . 'T23:59:59+00:00', $response->get_data()->availability[0]->end );

		ClockMock::reset();
	}
}
