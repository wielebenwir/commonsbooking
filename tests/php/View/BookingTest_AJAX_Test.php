<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Service\TimeframeExport;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use SlopeIt\ClockMock\ClockMock;

class BookingTest_AJAX_Test extends \WP_Ajax_UnitTestCase {

	private $itemID;
	private $locationID;
	private $timeframeID;
	private array $bookingCodes = [
		"turn",
		"and",
		"face",
		"the",
		"strange",
		"ch-ch-changes"
	];

	public function testGetBookingCode_AJAX() {
		$_POST['_wpnonce'] = wp_create_nonce( 'cb_get_booking_code' );
		$data = [
			'locationID' => $this->locationID,
			'itemID'     => $this->itemID,
			//format like "10/12/2023"
			'startDate'  => date( 'm/d/Y', strtotime( CustomPostTypeTest::CURRENT_DATE ) ),
		];
		$_POST['data'] = $data;
		//first case: booking code set
		try {
			$this->_handleAjax( 'cb_get_booking_code' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		//we have to remove the deprecation warnings from the response
		$response = explode( "\n", $this->_last_response );
		$response = json_decode( end( $response ) );
		$this->assertTrue ( $response->success );
		$this->assertContains( $response->bookingCode, $this->bookingCodes );
	}

	public function testGetLocationForItem_AJAX() {
		ClockMock::freeze( new \DateTime( CustomPostTypeTest::CURRENT_DATE ));
		$_POST['_wpnonce'] = wp_create_nonce( 'cb_get_bookable_location' );
		$data = [
			'itemID' => $this->itemID,
		];
		$_POST['data'] = $data;
		//first case: timeframe setup correctly
		try {
			$this->_handleAjax( 'cb_get_bookable_location' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		//we have to remove the deprecation warnings from the response
		$response = explode( "\n", $this->_last_response );
		$jsonResponse      = end( $response );
		$this->assertJson($jsonResponse);
		$response = json_decode( $jsonResponse );
		$this->assertTrue ( $response->success );
		$this->assertEquals( $response->locationID, $this->locationID );
		$this->assertTrue( $response->fullDay );
	}

	public function set_up() {
		parent::set_up();
		add_action( 'wp_ajax_cb_get_bookable_location', array( \CommonsBooking\View\Booking::class, 'getLocationForItem_AJAX' ) );
		add_action( 'wp_ajax_cb_get_booking_code', array( \CommonsBooking\View\Booking::class, 'getBookingCode_AJAX' ) );

		$now = new \DateTime(CustomPostTypeTest::CURRENT_DATE );
		$inTwoWeeks = clone $now;
		$inTwoWeeks->modify( '+2 weeks' );

		//create items and locations. We can't use the functions from the CustomPostTypeTest class because this class extends WP_Ajax_UnitTestCase
		$this->itemID = wp_insert_post( [
			'post_title'  => "AJAX Test Item",
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
			'post_status' => 'publish'
		] );
		$this->locationID = wp_insert_post( [
			'post_title'  => "AJAX Test Location",
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
			'post_status' => 'publish'
		] );

		// Create Timeframe
		$this->timeframeID = wp_insert_post( [
			'post_title'  => "AJAX Test Timeframe",
			'post_type'   => Timeframe::$postType,
			'post_status' => 'publish',
			'post_author' => 1
		] );

		update_post_meta( $this->timeframeID, 'type', Timeframe::BOOKABLE_ID );
		update_post_meta( $this->timeframeID, 'location-id', $this->locationID );
		update_post_meta( $this->timeframeID, 'item-id', $this->itemID );
		update_post_meta( $this->timeframeID, 'timeframe-max-days', 3 );
		update_post_meta( $this->timeframeID, 'timeframe-advance-booking-days', 30 );
		update_post_meta( $this->timeframeID, 'booking-startday-offset', 0 );
		update_post_meta( $this->timeframeID, 'full-day', "on" );
		update_post_meta( $this->timeframeID, 'timeframe-repetition', "d" );
		update_post_meta( $this->timeframeID, 'repetition-start', $now->getTimestamp() );
		update_post_meta( $this->timeframeID, 'repetition-end', $inTwoWeeks->getTimestamp() );
		update_post_meta( $this->timeframeID, 'start-time', '8:00 AM' );
		update_post_meta( $this->timeframeID, 'end-time', '12:00 PM' );
		update_post_meta( $this->timeframeID, 'grid', 0 );
		update_post_meta( $this->timeframeID, 'show-booking-codes', "on" );
		update_post_meta( $this->timeframeID, 'create-booking-codes', "on" );

		$bookingCodesString = implode( ',', $this->bookingCodes );

		//init booking code table and initial booking codes for timeframe
		Settings::updateOption('commonsbooking_options_bookingcodes','bookingcodes',$bookingCodesString);
		\CommonsBooking\Repository\BookingCodes::initBookingCodesTable();
		$timeframeCPT = new Timeframe();
		$timeframeCPT->savePost( $this->timeframeID, get_post($this->timeframeID) );
	}

	public function tear_down() {
		parent::tear_down();
		remove_action( 'wp_ajax_cb_get_bookable_location', array( \CommonsBooking\View\Booking::class, 'getLocationForItem_AJAX' ) );
		remove_action( 'wp_ajax_cb_get_booking_code', array( \CommonsBooking\View\Booking::class, 'getBookingCode_AJAX' ) );
		wp_delete_post( $this->itemID, true );
		wp_delete_post( $this->locationID, true );
		wp_delete_post( $this->timeframeID, true );
		$this->tearDownBookingCodesTable(); // counterpart of BookingCodes::initBookingCodesTable() in setUp()
	}

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . \CommonsBooking\Repository\BookingCodes::$tablename;
		$sql = "DROP TABLE $table_name";

		$result = $wpdb->query($sql);
	}
}
