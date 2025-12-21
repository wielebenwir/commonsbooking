<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use SlopeIt\ClockMock\ClockMock;

class AvailabilityRouteTest extends CB_REST_Route_UnitTestCase {

	const USER_ID       = 1;
	const CURRENT_DATE  = '2021-05-21';
	protected $ENDPOINT = '/commonsbooking/v1/availability';
	private array $locationIds;
	private array $itemIds;
	private array $timeframeIds;

	private $locationId;
	private $itemId;

	public function setUp(): void {
		parent::setUp();

		// TODO creates initial data (should be mocked in the future)
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		// Create location
		$this->locationId = self::createLocation( 'Testlocation', 'publish' );

		// Create Item
		$this->itemId = self::createItem( 'TestItem', 'publish' );

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

	protected function tearDown(): void {
		foreach ( $this->timeframeIds as $timeframeId ) {
			wp_delete_post( $timeframeId, true );
		}
		foreach ( $this->locationIds as $locationId ) {
			wp_delete_post( $locationId, true );
		}
		foreach ( $this->itemIds as $itemId ) {
			wp_delete_post( $itemId, true );
		}
		parent::tearDown();
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

	protected function createTimeframe(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$type = Timeframe::BOOKABLE_ID,
		$fullday = 'on',
		$repetition = 'w',
		$grid = 0,
		$startTime = '8:00 AM',
		$endTime = '12:00 PM',
		$postStatus = 'publish',
		$weekdays = [ '1', '2', '3', '4', '5', '6', '7' ],
		$postAuthor = self::USER_ID,
		$maxDays = 3,
		$advanceBookingDays = 30,
		$showBookingCodes = 'on',
		$createBookingCodes = 'on',
		$postTitle = 'TestTimeframe'
	) {
		// Create Timeframe
		$timeframeId = wp_insert_post(
			[
				'post_title'  => $postTitle,
				'post_type'   => Timeframe::$postType,
				'post_status' => $postStatus,
				'post_author' => $postAuthor,
			]
		);

		update_post_meta( $timeframeId, 'type', $type );
		update_post_meta( $timeframeId, 'location-id', $locationId );
		update_post_meta( $timeframeId, 'item-id', $itemId );
		update_post_meta( $timeframeId, 'timeframe-max-days', $maxDays );
		update_post_meta( $timeframeId, 'timeframe-advance-booking-days', $advanceBookingDays );
		update_post_meta( $timeframeId, 'full-day', $fullday );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_REPETITION, $repetition );
		if ( $repetitionStart ) {
			update_post_meta( $timeframeId, 'repetition-start', $repetitionStart );
		}
		if ( $repetitionEnd ) {
			update_post_meta( $timeframeId, 'repetition-end', $repetitionEnd );
		}

		update_post_meta( $timeframeId, 'start-time', $startTime );
		update_post_meta( $timeframeId, 'end-time', $endTime );
		update_post_meta( $timeframeId, 'grid', $grid );
		update_post_meta( $timeframeId, 'weekdays', $weekdays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES, $showBookingCodes );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES, $createBookingCodes );

		$this->timeframeIds[] = $timeframeId;

		return $timeframeId;
	}

	public function createLocation( $title, $postStatus, $admins = [] ) {
		$locationId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Location::$postType,
				'post_status' => $postStatus,
			]
		);

		$this->locationIds[] = $locationId;

		if ( ! empty( $admins ) ) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		return $locationId;
	}

	public function createItem( $title, $postStatus, $admins = [] ) {
		$itemId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Item::$postType,
				'post_status' => $postStatus,
			]
		);

		$this->itemIds[] = $itemId;

		if ( ! empty( $admins ) ) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}
}
