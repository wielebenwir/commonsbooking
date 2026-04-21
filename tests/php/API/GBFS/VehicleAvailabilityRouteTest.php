<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use SlopeIt\ClockMock\ClockMock;

class VehicleAvailabilityRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/vehicle_availability.json';
	private $start;
	private $end;
	private $timeframe;

	public function testDailyAvailability() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;
		$this->assertNotEmpty( $data->vehicles );
		$this->assertCount( 1, $data->vehicles );
		$availabilities = $data->vehicles[0]->availabilities;
		$this->assertCount( 1, $availabilities );

		$startDT = new \DateTime( $availabilities[0]->from );
		$today   = new \DateTime( self::CURRENT_DATE );

		$this->assertEqualsWithDelta( $today->getTimestamp(), $startDT->getTimestamp(), 1.0 );
	}

	public function testHourlyAvailability() {
		delete_post_meta( $this->timeframe, 'full-day', 'on' );
		update_post_meta( $this->timeframe, 'grid', 1 ); // hourly grid
		update_post_meta( $this->timeframe, 'start-time', '08:00 AM' );
		update_post_meta( $this->timeframe, 'end-time', '01:00 PM' );

		$startDT = new \DateTime();
		$startDT->modify( '08:00 AM' );
		$endDT = new \DateTime();
		$endDT->modify( '01:00 PM' );
		$endDT->modify( '-1 second' ); // timeframes always have one second cut

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;
		$this->assertNotEmpty( $data->vehicles );
		$this->assertCount( 1, $data->vehicles );
		$availabilities = $data->vehicles[0]->availabilities;
		$this->assertCount( 2, $availabilities ); // today and tomorrow
		$this->assertEquals( $startDT->format( 'c' ), $availabilities[0]->from );
		$this->assertEquals( $endDT->format( 'c' ), $availabilities[0]->until );
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
