<?php

namespace CommonsBooking\Tests\API;

class LocationsRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/locations';

	private $locationId;

	public function setUp(): void {
		parent::setUp();
		$this->locationId = $this->createLocation( 'Testlocation', 'publish', [] );
	}

	public function testLocationsSuccess() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertObjectHasProperty( 'locations', $data );
		$this->assertEquals( 'FeatureCollection', $data->locations->type );
		$this->assertNotEmpty( $data->locations->features );
		$this->assertCount( 1, $data->locations->features );

		$feature = $data->locations->features[0];
		$this->assertEquals( 'Feature', $feature->type );
		$this->assertEquals( (string) $this->locationId, $feature->properties->id );
		$this->assertEquals( 'Testlocation', $feature->properties->name );
	}

	public function testSingleLocation() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT . '/' . $this->locationId );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertObjectHasProperty( 'locations', $data );
		$this->assertCount( 1, $data->locations->features );
		$this->assertEquals( (string) $this->locationId, $data->locations->features[0]->properties->id );
	}

	public function testMultipleLocations() {
		$secondId = $this->createLocation( 'Second Location', 'publish', [] );

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data()->locations->features );

		$ids = array_map(
			fn( $f ) => $f->properties->id,
			$response->get_data()->locations->features
		);
		$this->assertContains( (string) $this->locationId, $ids );
		$this->assertContains( (string) $secondId, $ids );
	}

	public function testEmptyLocations() {
		// Remove the location created in setUp
		wp_delete_post( array_pop( $this->locationIds ), true );

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals( 'FeatureCollection', $response->get_data()->locations->type );
		$this->assertCount( 0, $response->get_data()->locations->features );
	}
}
