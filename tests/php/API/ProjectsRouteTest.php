<?php

namespace CommonsBooking\Tests\API;

class ProjectsRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/projects';

	public function testProjectsSuccess() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertObjectHasProperty( 'projects', $data );
		$this->assertIsArray( $data->projects );
		$this->assertCount( 1, $data->projects );

		$project = $data->projects[0];
		$this->assertObjectHasProperty( 'id', $project );
		$this->assertObjectHasProperty( 'name', $project );
		$this->assertObjectHasProperty( 'url', $project );
		$this->assertObjectHasProperty( 'description', $project );
		$this->assertObjectHasProperty( 'language', $project );

		$this->assertEquals( '1', $project->id );
		$this->assertEquals( get_bloginfo( 'name' ), $project->name );
		$this->assertEquals( get_bloginfo( 'url' ), $project->url );
		$this->assertEquals( get_bloginfo( 'description' ), $project->description );
		$this->assertEquals( get_bloginfo( 'language' ), $project->language );
	}

	public function testSingleProjectEndpointReturnsSamePayloadAsCollectionEndpoint() {
		$collectionRequest  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$collectionResponse = rest_do_request( $collectionRequest );

		$singleRequest  = new \WP_REST_Request( 'GET', $this->ENDPOINT . '/1' );
		$singleResponse = rest_do_request( $singleRequest );

		$this->assertSame( 200, $singleResponse->get_status() );
		$this->assertEquals( $collectionResponse->get_data(), $singleResponse->get_data() );
	}
}
