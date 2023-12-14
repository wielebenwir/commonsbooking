<?php

namespace CommonsBooking\Service;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

/**
 * This class just exists for testing purposes on the experiment/queries-test branch.
 */
class FloodPosts
{

	const BOOKINGS_PER_ITEM = 50;
	const ITEMS_TOTAL = 200;

	function run() {
		if (!class_exists('WP_CLI')) {
			return;
		}
		$repetitions = [];
		$start = new \DateTime(CustomPostTypeTest::CURRENT_DATE);
		$end = new \DateTime(CustomPostTypeTest::CURRENT_DATE);
		$end->modify(self::BOOKINGS_PER_ITEM . ' days');
		$period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
		foreach ($period as $date) {
			$startTs = $date->getTimestamp();
			$date->modify('23:59:59');
			$endTs = $date->getTimestamp();
			$repetitions[] = [
				"start" => $startTs,
				"end" => $endTs,
			];
		}

		\WP_CLI::log("Creating " . self::ITEMS_TOTAL . " items with " . self::BOOKINGS_PER_ITEM . " bookings each");
		\WP_CLI::log("Total bookings: " . (self::ITEMS_TOTAL * self::BOOKINGS_PER_ITEM));

		for ($i = 0; $i < self::ITEMS_TOTAL; $i++) {
			\WP_CLI::log("Creating item $i");
			$item = $this->createItem("Item $i");
			$location = $this->createLocation("Location $i");
			$timeframe = $this->createTimeframe(
				$location,
				$item,
				$start->getTimestamp(),
				null //Make timeframe infinite
			);
			foreach ($repetitions as $repetition) {
				$this->createBooking(
					$location,
					$item,
					$repetition["start"],
					$repetition["end"]
				);
			}
		}

		\WP_CLI::success("Done");
	}

	//extracted from CustomPostTypeTest because they were object oriented on Test class
	// Create Item
	function createItem($title, $postStatus = 'publish', $admins = []) {
		$itemId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Item::$postType,
			'post_status' => $postStatus
		] );

		if (! empty($admins)) {
			update_post_meta( $itemId, COMMONSBOOKING_METABOX_PREFIX . 'item_admins', $admins );
		}

		return $itemId;
	}

	// Create Location
	function createLocation($title, $postStatus = 'publish', $admins = []) {
		$locationId = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => Location::$postType,
			'post_status' => $postStatus
		] );

		if (! empty($admins)) {
			update_post_meta( $locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_admins', $admins );
		}

		return $locationId;
	}

	function createTimeframe(
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
		$manualSelectionDays = "",
		$postAuthor = CustomPostTypeTest::USER_ID,
		$maxDays = 3,
		$advanceBookingDays = 30,
		$bookingStartdayOffset = 0,
		$showBookingCodes = "off",
		$createBookingCodes = "off",
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
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, $advanceBookingDays );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET, $bookingStartdayOffset );
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
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_MANUAL_SELECTION, $manualSelectionDays);
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES, $showBookingCodes );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES, $createBookingCodes );

		return $timeframeId;
	}

	function createBooking(
		$locationId,
		$itemId,
		$repetitionStart,
		$repetitionEnd,
		$startTime = '0:00 AM',
		$endTime = '23:59 PM',
		$postStatus = 'confirmed',
		$postAuthor = CustomPostTypeTest::USER_ID,
		$timeframeRepetition = 'w',
		$timeframeMaxDays = 3,
		$postTitle = 'Booking',
		$grid = 0,
		$weekdays = [ "1", "2", "3", "4", "5", "6", "7" ]
	) {
		// Create booking
		$bookingId = wp_insert_post( [
			'post_title'  => $postTitle,
			'post_type'   => Booking::$postType,
			'post_status' => $postStatus,
			'post_author' => $postAuthor
		] );

		update_post_meta( $bookingId, 'type', Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, 'timeframe-repetition', $timeframeRepetition );
		update_post_meta( $bookingId, 'start-time', $startTime );
		update_post_meta( $bookingId, 'end-time', $endTime );
		update_post_meta( $bookingId, 'timeframe-max-days', $timeframeMaxDays );
		update_post_meta( $bookingId, 'location-id', $locationId );
		update_post_meta( $bookingId, 'item-id', $itemId );
		update_post_meta( $bookingId, 'grid', $grid );
		update_post_meta( $bookingId, 'repetition-start', $repetitionStart );
		update_post_meta( $bookingId, 'repetition-end', $repetitionEnd );
		update_post_meta( $bookingId, 'weekdays', $weekdays );

		return $bookingId;
	}

}