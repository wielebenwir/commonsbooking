<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostType_AJAX_Test;
use SlopeIt\ClockMock\ClockMock;

class BookingTest_AJAX_Test extends CustomPostType_AJAX_Test {

	protected $hooks            = [
		'cb_get_bookable_location' => array(
			\CommonsBooking\View\Booking::class,
			'getLocationForItem_AJAX',
		),
		'cb_get_booking_code' => array(
			\CommonsBooking\View\Booking::class,
			'getBookingCode_AJAX',
		),
	];
	private array $bookingCodes = [
		'turn',
		'and',
		'face',
		'the',
		'strange',
		'ch-ch-changes',
	];

	public function testGetBookingCode_AJAX() {
		ClockMock::freeze( new \DateTime( CustomPostTypeTest::CURRENT_DATE ) );

		// Save timeframe post to trigger booking code generation.
		// It is necessary to generate here after time has been frozen to CustomPostTypeTest::CURRENT_DATE
		// because the code generation depends on the current date and codes are not generated for the past.
		// (CustomPostTypeTest::CURRENT_DATE is a date in the past)
		$timeframeCPT = new Timeframe();
		$timeframeCPT->savePost( $this->timeframeID, get_post( $this->timeframeID ) );

		$data          = [
			'locationID' => $this->locationID,
			'itemID'     => $this->itemID,
			// format like "10/12/2023"
			'startDate'  => date( 'm/d/Y', strtotime( CustomPostTypeTest::CURRENT_DATE ) ),
		];
		$_POST['data'] = $data;
		// first case: booking code set
		$response = $this->runHook( 'cb_get_booking_code' );
		$this->assertTrue( $response->success );
		$this->assertContains( $response->bookingCode, $this->bookingCodes );
	}

	public function testGetLocationForItem_AJAX() {
		ClockMock::freeze( new \DateTime( CustomPostTypeTest::CURRENT_DATE ) );
		$data          = [
			'itemID' => $this->itemID,
		];
		$_POST['data'] = $data;
		// first case: timeframe setup correctly
		$response = $this->runHook( 'cb_get_bookable_location' );
		$this->assertTrue( $response->success );
		$this->assertEquals( $response->locationID, $this->locationID );
		$this->assertTrue( $response->fullDay );
	}

	public function set_up() {
		parent::set_up();
		$bookingCodesString = implode( ',', $this->bookingCodes );
		// init booking code table
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes', $bookingCodesString );
		\CommonsBooking\Repository\BookingCodes::initBookingCodesTable();
		$this->createTimeframe();
	}

	public function tear_down() {
		parent::tear_down();
		$this->tearDownBookingCodesTable(); // counterpart of BookingCodes::initBookingCodesTable() in setUp()
	}

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . \CommonsBooking\Repository\BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$result     = $wpdb->query( $sql );
	}
}
