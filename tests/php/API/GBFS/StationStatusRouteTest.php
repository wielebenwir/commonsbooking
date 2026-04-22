<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class StationStatusRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/station_status.json';
	public function testBasicStationStatus() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;

		$this->assertNotEmpty( $data->stations );
		$this->assertCount( 1, $data->stations );

		$station = $data->stations[0];

		$this->assertEquals( (string) $this->locationId, $station->station_id );
		$this->assertEquals( 1, $station->num_vehicles_available );
		$this->assertTrue( $station->is_installed );
		$this->assertTrue( $station->is_renting );
		$this->assertTrue( $station->is_returning );

		$reported = new \DateTime( $station->last_reported );
		$now      = new \DateTime( self::CURRENT_DATE );

		$this->assertEqualsWithDelta( $now->getTimestamp(), $reported->getTimestamp(), 1.0 );
	}

	public function testStationStatus_whenBooked_isEmpty() {
		$booking = $this->createConfirmedBookingStartingToday();

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$data    = $response->get_data()->data;
		$station = $data->stations[0];

		$this->assertEquals( 0, $station->num_vehicles_available );
	}

	public function testStationStatus_afterTimeframeEnd_isEmpty() {
		$future = new \DateTime( self::CURRENT_DATE );
		$future->modify( '+11 days' );
		ClockMock::freeze( $future );

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$data    = $response->get_data()->data;
		$station = $data->stations[0];

		$this->assertEquals( 0, $station->num_vehicles_available );
	}

	public function testStationStatus_withBookingOffset() {
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

		// with offset → unavailable
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data()->data;

		$relevantStation = current(
			array_filter(
				$data->stations,
				function ( $station ) use ( $otherLocationId ) {
					return $station->station_id === (string) $otherLocationId;
				}
			)
		);
		$this->assertEquals( 0, $relevantStation->num_vehicles_available );

		// remove offset → available
		update_post_meta( $timeframeID, \CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET, 0 );

		$response        = rest_do_request( $request );
		$data            = $response->get_data()->data;
		$relevantStation = current(
			array_filter(
				$data->stations,
				function ( $station ) use ( $otherLocationId ) {
					return $station->station_id === (string) $otherLocationId;
				}
			)
		);
		$this->assertEquals( 1, $relevantStation->num_vehicles_available );
	}

	public function setUp(): void {
		parent::setUp();

		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$this->locationId = $this->createLocation( 'Testlocation', 'publish' );
		$this->itemId     = $this->createItem( 'TestItem', 'publish' );

		$this->timeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) )
		);
	}

	public function tearDown(): void {
		ClockMock::reset();
		parent::tearDown();
	}
}
