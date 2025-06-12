<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\BookingCodes;
use CommonsBooking\Settings\Settings;
use SlopeIt\ClockMock\ClockMock;


/**
 * @group email_bookingcodes
 */

class BookingCodesTest extends CustomPostTypeTest {

	protected const bookingDaysInAdvance = 35;

	protected const timeframeStart = 0;

	protected const timeframeEnd = 100;

	protected const bookingCodes = array( 'BOOKINGCODE1', 'BOOKINGCODE2', 'BOOKINGCODE3' );

	protected $timeframeId;

	/* Tests if booking codes table is displayed and contains codes */
	public function testRenderTable() {
		$this->expectOutputRegex( '/' . implode( '|', self::bookingCodes ) . '/' );
		BookingCodes::renderTable( $this->timeframeId );
	}

	/* Tests if booking direct email of booking codes contains codes */
	public function testEmailCodes() {

		delete_transient( \CommonsBooking\Model\BookingCode::ERROR_TYPE );
		reset_phpmailer_instance();
		$email = tests_retrieve_phpmailer_instance();

		try {
			BookingCodes::emailCodes( $this->timeframeId, time(), strtotime( '+10day' ) );
			$e_data = [];
		} catch ( \Exception $e ) {
			$e_data = json_decode( $e->getMessage(), true );
		}

		$this->assertFalse( get_transient( \CommonsBooking\Model\BookingCode::ERROR_TYPE ) );
		$this->assertNotEmpty( $e_data );
		$this->assertStringEndsWith( '#email-booking-codes-list', $e_data['location'] );
		$this->assertMatchesRegularExpression( '/' . implode( '|', self::bookingCodes ) . '/', $email->get_sent()->body );
	}

	/* As above but cover the case when ical is attached */
	public function testEmailCodesWithIcal() {

		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'mail-booking-codes-attach-ical', 'on' );

		$this->testEmailCodes();
	}

	public function testInitialCronEmailEvent() {
		$todayDate = new \DateTime( self::CURRENT_DATE );
		ClockMock::freeze( $todayDate );

		// Case 1: Send every month starting from tomorrow
		$tomorrow = clone $todayDate;
		$tomorrow->modify( '+1 day' );
		$expectedNextSendDate = clone $tomorrow;
		$actualNextSendDate   = BookingCodes::initialCronEmailEvent( $tomorrow->getTimestamp(), 1 );
		$this->assertEquals( $expectedNextSendDate->getTimestamp(), $actualNextSendDate->getTimestamp() );

		// Case 2: Send every month starting from day in past (should be next month) - we set arbitrary dates for this test to make it easier to understand
		ClockMock::freeze( new \DateTime( '15.07.2021' ) );
		$configuredAsStartDate = new \DateTime( '12.06.2021' );
		$expectedNextSendDate  = new \DateTime( '12.08.2021' );
		$actualNextSendDate    = BookingCodes::initialCronEmailEvent( $configuredAsStartDate->getTimestamp(), 1 );
		$this->assertEquals( $expectedNextSendDate->getTimestamp(), $actualNextSendDate->getTimestamp() );

		// Case 3: Send every 2 months starting from day in past (should be next month)
		ClockMock::freeze( new \DateTime( '15.07.2021' ) );
		$configuredAsStartDate = new \DateTime( '12.06.2021' );
		$expectedNextSendDate  = new \DateTime( '12.08.2021' );
		$actualNextSendDate    = BookingCodes::initialCronEmailEvent( $configuredAsStartDate->getTimestamp(), 2 );
		$this->assertEquals( $expectedNextSendDate->getTimestamp(), $actualNextSendDate->getTimestamp() );

		// Case 4: Send every 2 months starting from day just shortly in past (should still start at start date)
		// A little bit confusing but this will NOT start at the next available date immediatly but at the date plus x months. Is that intended?
		ClockMock::freeze( new \DateTime( '15.07.2021' ) );
		$configuredAsStartDate = new \DateTime( '12.07.2021' );
		$expectedNextSendDate  = new \DateTime( '12.09.2021' );
		$actualNextSendDate    = BookingCodes::initialCronEmailEvent( $configuredAsStartDate->getTimestamp(), 2 );
		$this->assertEquals( $expectedNextSendDate->getTimestamp(), $actualNextSendDate->getTimestamp() );
	}

	public static function on_wp_redirect( $location, $status ) {
		throw new \Exception(
			json_encode(
				[
					'location' => $location,
					'status'   => $status,
				]
			)
		);
	}

	protected function deleteCBOptions() {
		foreach ( wp_load_alloptions() as $option => $value ) {
			if ( str_starts_with( $option, COMMONSBOOKING_PLUGIN_SLUG . '_options' ) ) {
				delete_option( $option );
			}
		}
	}

	protected function setUp(): void {
		parent::setUp();
		// set default options for email templates
		\CommonsBooking\Wordpress\Options\AdminOptions::setOptionsDefaultValues();

		// set defined booking codes option
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes', implode( ',', self::bookingCodes ) );
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes-listed-timeframe', 42 );

		$now               = time();
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+' . self::timeframeStart . ' days midnight', $now ),
			strtotime( '+' . self::timeframeEnd . ' days midnight', $now )
		);

		// force save_post action to generate booking codes
		$timeframePost = get_post( $this->timeframeId );
		do_action( 'save_post', $this->timeframeId, $timeframePost, true );

		// set Location email
		$timeframe = new Timeframe( $this->timeframeId );
		update_post_meta( $timeframe->getLocation()->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_email', 'dummy_email1@nowhere.com, dummy_email2@everywhere.de' );

		// setup the wp_redirect "mock"
		add_filter( 'wp_redirect', array( __CLASS__, 'on_wp_redirect' ), 1, 2 );
	}



	protected function tearDown(): void {
		remove_filter( 'wp_redirect', array( __CLASS__, 'on_wp_redirect' ), 1 );
		delete_transient( \CommonsBooking\Model\BookingCode::ERROR_TYPE );
		$this->deleteCBOptions();
		parent::tearDown();
	}
}
