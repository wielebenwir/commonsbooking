<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Booking;

final class BookingTest extends CustomPostTypeTest {


	protected function setUp(): void {
		parent::setUp();
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			time() - 86400,
			time() + 86400
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetBookingListData() {
		wp_set_current_user( self::USER_ID );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );

		// check for #1802, delete location. Booking list should still generate
		wp_delete_post( $this->locationId, true );
		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] == 1 );
	}

	public function testGetBookingListData_withNullFilter() {
		wp_set_current_user( self::USER_ID );

		$nullCallback = function ( $rowData, $bookingObject ) {
			return null;
		};

		add_filter( 'commonsbooking_booking_filter', $nullCallback, 10, 2 );

		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] === 0 );

		remove_filter( 'commonsbooking_booking_filter', $nullCallback );
	}

	public function testGetBookingListData_withSimpleFilter() {
		wp_set_current_user( self::USER_ID );

		$simpleFilter = function ( $rowData, $bookingObject ) {
			if ( in_array( $rowData['postID'], $this->bookingIds, true ) ) {
				return $rowData;
			} else {
				return null;
			}
		};

		add_filter( 'commonsbooking_booking_filter', $simpleFilter, 10, 2 );

		$bookings = Booking::getBookingListData();
		$this->assertTrue( $bookings['total'] === 1 );

		remove_filter( 'commonsbooking_booking_filter', $simpleFilter );
	}

	/*
	TODO implement more complex test (to test keys of rowData as input/filter and as output via getBookingListData
	public function testGetBookingListData_withComplexRowDataFilter() {

	}
	*/

	public function testGetBookingListiCal() {
		$otherTestItem     = $this->createItem( 'OtherTestItem' );
		$otherTestLocation = $this->createLocation( 'OtherTestLocation' );

		$OWNBOOKING_EXPECTED_TITLE = 'Your booking for TestItem';
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'emailtemplates_mail-booking_ics_event-title', 'Your booking for {{item:post_title}}' );
		$OWNBOOKING_EXPECTED_DESC = 'Your Location: Testlocation';
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'emailtemplates_mail-booking_ics_event-description', 'Your Location: {{location:post_title}}' );
		$OTHERBOOKING_EXPECTED_TITLE = 'OtherTestItem @ normaluser';
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title', '{{item:post_title}} @ {{user:user_login}}' );
		$OTHERBOOKING_EXPECTED_DESC = 'Their Location: OtherTestLocation';
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_desc', 'Their Location: {{location:post_title}}' );

		self::createSubscriber();
		$this->createBooking(
			$otherTestLocation,
			$otherTestItem,
			time() + 3600,
			time() + 7200,
			'12:00 AM',
			'23:59',
			'confirmed',
			$this->subscriberId
		);

		wp_set_current_user( self::USER_ID );
		$bookingListiCal = Booking::getBookingListiCal();
		$bookingListiCal = explode( "\r\n", $bookingListiCal );
		$bookingListiCal = array_filter( $bookingListiCal ); // remove empty lines
		$this->assertIsArray( $bookingListiCal );
		$this->assertNotEmpty( $bookingListiCal );
		$this->assertContains( 'SUMMARY:' . $OWNBOOKING_EXPECTED_TITLE, $bookingListiCal );
		$this->assertContains( 'DESCRIPTION:' . $OWNBOOKING_EXPECTED_DESC, $bookingListiCal );
		$this->assertContains( 'SUMMARY:' . $OTHERBOOKING_EXPECTED_TITLE, $bookingListiCal );
		$this->assertContains( 'DESCRIPTION:' . $OTHERBOOKING_EXPECTED_DESC, $bookingListiCal );
	}
}
