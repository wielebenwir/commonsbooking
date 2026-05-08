<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
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

	public function testAvailabilityWithOffset() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		// delete other timeframe so it doesn't mess with our tests
		wp_delete_post( $this->timeframe, true );
		$otherLocationId = $this->createLocation( 'Other Location', );
		$otherItemId     = $this->createItem( 'Other Item', );

		$timeframeID = $this->createTimeframe(
			$otherLocationId,
			$otherItemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'd',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			'',
			CustomPostTypeTest::USER_ID,
			3,
			30,
			2
		);

		// with offset → not available right now
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data()->data;

		$availabilities = $data->vehicles[0]->availabilities;

		$startDT = new \DateTime( $availabilities[0]->from );
		$today   = new \DateTime( self::CURRENT_DATE );

		$this->assertNotEqualsWithDelta( $today->getTimestamp(), $startDT->getTimestamp(), 1.0 );

		// remove offset → now available today
		update_post_meta( $timeframeID, \CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET, 0 );

		$response       = rest_do_request( $request );
		$data           = $response->get_data()->data;
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

	public function testVehicleIDChanges() {
		// test, that the vehicle ID rotates after each trip
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;
		$id   = $data->vehicles[0]->vehicle_id;

		// add a trip in the past
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) )
		);

		// after the trip, the vehicle ID should have changed
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;

		$this->assertNotEquals( $id, $data->vehicles[0]->vehicle_id );
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
