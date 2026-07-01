<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\Tests\API\CB_REST_Route_UnitTestCase;
use SlopeIt\ClockMock\ClockMock;

class VehicleStatusRouteTest extends CB_REST_Route_UnitTestCase {

	protected $ENDPOINT = '/commonsbooking/v1/vehicle_status.json';
	private $start;
	private $end;
	private $timeframe;

	public function testIsBooked() {
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()->data;

		$this->assertCount( 1, $data->vehicles );

		$this->createConfirmedBookingStartingToday();
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertEmpty( $data->vehicles );
	}

	/**
	 * Item should not appear, when it is currently not available for rent.
	 *
	 * @return void
	 */
	public function testNoTimeframe() {
		// base case, not disabled
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );

		// timeframe expired: item vanishes from feed
		$future = $this->end->modify( '+1 day' );
		ClockMock::freeze( $future );
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertEmpty( $data->vehicles );
	}

	/**
	 * This field is used to indicate vehicles that are in the field but not available for rental due to a mechanical issue or low battery etc.
	 * Publishing this data may prevent users from attempting to rent vehicles that are disabled and not available for rental.
	 * This field SHOULD NOT be set to true when the system is closed for vehicles that would otherwise be rentable.
	 *
	 * In CB, this is handled through restrictions. When a total breakdown is present, the item is disabled.
	 * @return void
	 */
	public function testIsDisabled() {
		// base case, not disabled
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );

		// restriction present: should not disappear from feed, but is considered disabled
		$this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertCount( 1, $data->vehicles );
		$this->assertTrue( $data->vehicles[0]->is_disabled );
	}

	public function testIsDisabled_hint() {
		// hints should not affect availability
		$this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );
	}

	public function testIsDisabled_inFuture() {
		// restrictions in the future should not affect current state
		$this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );
	}

	public function testIsDisabled_inactiveRestriction() {
		// inactive restrictions should also not trigger
		$this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null,
			\CommonsBooking\Model\Restriction::STATE_SOLVED
		);

		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertFalse( $data->vehicles[0]->is_disabled );
	}

	public function testExclusion() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		update_post_meta( $this->itemId, COMMONSBOOKING_METABOX_PREFIX . 'api_exclude', 'on' );
		$request  = new \WP_REST_Request( 'GET', $this->ENDPOINT );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data()->data;
		$this->assertEmpty( $data->vehicles );
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
