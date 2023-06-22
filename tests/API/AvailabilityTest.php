<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class AvailabilityTest extends \WP_UnitTestCase {

	const USER_ID = 1;
	protected $ENDPOINT = '/commonsbooking/v1/availability';

	protected $server;

	/**
	 * TODO move to abstract api test router
	 */
	public function setUp() : void {
		parent::setUp();
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;

		// Enables api
		Settings::updateOption( 'commonsbooking_options_api', 'api-activated' , 'on' );
		Settings::updateOption( 'commonsbooking_options_api', 'apikey_not_required', 'on' );

		// Registers routes (via rest_api_init hook)
		( new Plugin() )->initRoutes();

		// Applies hook
		do_action( 'rest_api_init' );

		// Create location
		$this->locationId = self::createLocation('Testlocation', 'publish');

		// Create Item
		$this->itemId = self::createItem('TestItem', 'publish');

		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day' ),
			strtotime( '+1 day' )
		);
	}

	// TODO move to abstract test case
	public function testRoutes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->ENDPOINT, $routes );
	}

	public function testsAvailabilitySuccess() {

		$request = new \WP_REST_Request( 'GET', $this->ENDPOINT );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 2, count( $response->get_data()->availability ) );
		$this->assertEquals( $this->locationId, $response->get_data()->availability[0]->locationId );
		$this->assertEquals( $this->itemId, $response->get_data()->availability[0]->itemId );
	}

	protected function createTimeframe(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$type = Timeframe::BOOKABLE_ID,
		$fullday = "on",
		$repetition = 'w',
		$grid = 0,
		$startTime = '8:00 AM',
		$endTime = '12:00 PM',
		$postStatus = 'publish',
		$weekdays = [ "1", "2", "3", "4", "5", "6", "7" ],
		$postAuthor = self::USER_ID,
		$maxDays = 3,
		$advanceBookingDays = 30,
		$showBookingCodes = "on",
		$createBookingCodes = "on",
		$postTitle = 'TestTimeframe'
	) {
		// Create Timeframe
		$timeframeId = wp_insert_post( [
			'post_title'  => $postTitle,
			'post_type'   => Timeframe::$postType,
			'post_status' => $postStatus,
			'post_author' => $postAuthor
		] );

		update_post_meta( $timeframeId, 'type', $type );
		update_post_meta( $timeframeId, 'location-id', $locationId );
		update_post_meta( $timeframeId, 'item-id', $itemId );
		update_post_meta( $timeframeId, 'timeframe-max-days', $maxDays );
		update_post_meta( $timeframeId, 'timeframe-advance-booking-days', $advanceBookingDays );
		update_post_meta( $timeframeId, 'full-day', $fullday );
		update_post_meta( $timeframeId, 'timeframe-repetition', $repetition );
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
		update_post_meta( $timeframeId, 'show-booking-codes', $showBookingCodes );
		update_post_meta( $timeframeId, 'create-booking-codes', $createBookingCodes );

		$this->timeframeIds[] = $timeframeId;

		return $timeframeId;
	}

	public function createLocation($title, $postStatus, $admins = []) {
		$locationId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Location::$postType,
			'post_status' => $postStatus
		] );

		$this->locationIds[] = $locationId;

		if (! empty($admins)) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		return $locationId;
	}

	public function createItem($title, $postStatus, $admins = []) {
		$itemId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Item::$postType,
			'post_status' => $postStatus
		] );

		$this->itemIds[] = $itemId;

		if (! empty($admins)) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}
}
