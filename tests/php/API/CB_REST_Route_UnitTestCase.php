<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

/**
 * Abstract test case which implicitly tests REST Routes
 * and provides helpers to create the post types used by all route endpoints.
 */
class CB_REST_Route_UnitTestCase extends CB_REST_UnitTestCase {

	const USER_ID      = 1;
	const CURRENT_DATE = '2021-05-21';

	protected $ENDPOINT;

	protected array $locationIds  = [];
	protected array $itemIds      = [];
	protected array $timeframeIds = [];

	public function testRoute() {
		$this->assertNotNull( $this->ENDPOINT );
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $this->ENDPOINT, $routes );
	}

	protected function tearDown(): void {
		foreach ( $this->timeframeIds as $id ) {
			wp_delete_post( $id, true );
		}
		foreach ( $this->locationIds as $id ) {
			wp_delete_post( $id, true );
		}
		foreach ( $this->itemIds as $id ) {
			wp_delete_post( $id, true );
		}
		parent::tearDown();
	}

	protected function createLocation( $title, $postStatus, $admins = [] ) {
		$locationId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Location::$postType,
				'post_status' => $postStatus,
			]
		);

		if ( ! empty( $admins ) ) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		$this->locationIds[] = $locationId;

		return $locationId;
	}

	protected function createItem( $title, $postStatus, $admins = [] ) {
		$itemId = wp_insert_post(
			[
				'post_title'  => $title,
				'post_type'   => Item::$postType,
				'post_status' => $postStatus,
			]
		);

		if ( ! empty( $admins ) ) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		$this->itemIds[] = $itemId;

		return $itemId;
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
}
