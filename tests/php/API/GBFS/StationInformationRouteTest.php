<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use CommonsBooking\Tests\Helper\GeoHelperTest;
use WP_REST_Request;

class StationInformationRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/station_information.json';

	public function testBasicStationInformation_withLatLonMeta() {
		update_post_meta( $this->locationId, 'geo_latitude', '50.123' );
		update_post_meta( $this->locationId, 'geo_longitude', '8.123' );

		$request  = new WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;
		$this->assertNotEmpty( $data->stations );
		$this->assertCount( 1, $data->stations );

		$station = $data->stations[0];

		$this->assertEquals( (string) $this->locationId, $station->station_id );
		$this->assertNotEmpty( $station->name );
		$this->assertNotEmpty( $station->rental_uris->web );

		$this->assertEquals( 50.123, $station->lat );
		$this->assertEquals( 8.123, $station->lon );
	}

	// no gps data and no address defined, skip location
	public function testStationInformation_geocodingNonExistent() {
		delete_post_meta( $this->locationId, 'geo_latitude' );
		delete_post_meta( $this->locationId, 'geo_longitude' );

		$mockedLocationCoordinates = GeoHelperTest::mockedLocation()->getCoordinates();

		$request  = new WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$data = $response->get_data()->data;
		$this->assertEmpty( $data->stations );
	}

	public function setUp(): void {
		parent::setUp();

		$this->locationId = $this->createLocation( 'Test Location', 'publish' );
		$this->itemId     = $this->createItem( 'TestItem', 'publish' );

		$this->timeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) )
		);
	}
}
