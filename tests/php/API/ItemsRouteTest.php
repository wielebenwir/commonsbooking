<?php

namespace CommonsBooking\Tests\API;

use SlopeIt\ClockMock\ClockMock;

class ItemsRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/items';

	private $locationId;
	private $itemId;

	public function setUp(): void {
		parent::setUp();

		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$this->locationId = $this->createLocation( 'Testlocation', 'publish', [] );
		$this->itemId     = $this->createItem( 'TestItem', 'publish' );

		$mocked = new \DateTimeImmutable( self::CURRENT_DATE );
		$start  = $mocked->modify( '-1 days' );
		$end    = $mocked->modify( '+1 days' );

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$start->getTimestamp(),
			$end->getTimestamp()
		);

		ClockMock::reset();
	}

	public function testItemsSuccess() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data->items );
		$this->assertCount( 1, $data->items );

		$item = $data->items[0];
		$this->assertEquals( (string) $this->itemId, $item->id );
		$this->assertEquals( 'TestItem', $item->name );
		$this->assertNotEmpty( $item->url );
	}

	public function testItemsIncludesLocations() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertObjectHasProperty( 'locations', $data );
		$this->assertObjectHasProperty( 'type', $data->locations );
		$this->assertEquals( 'FeatureCollection', $data->locations->type );
	}

	public function testItemsIncludesAvailability() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertObjectHasProperty( 'availability', $data );
		$this->assertIsArray( $data->availability );
		$this->assertNotEmpty( $data->availability );

		ClockMock::reset();
	}

	public function testSingleItem() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT . '/' . $this->itemId );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data->items );
		$this->assertCount( 1, $data->items );
		$this->assertEquals( (string) $this->itemId, $data->items[0]->id );
	}
}
