<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\BookingCodes;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Settings\Settings;


/**
 * @group email_bookingcodes
 */

class BookingCodesTest extends CustomPostTypeTest {

	protected const bookingDaysInAdvance = 35;

	protected const timeframeStart = 0;

	protected const timeframeEnd = 100;

	protected const bookingCodes=array("BOOKINGCODE1","BOOKINGCODE2","BOOKINGCODE3");

	protected $timeframeId;

	/* Tests if cron event for email_bookingscodes is registered */
	public function testEmailBookingCodesScheduled() {
		$schedule=wp_get_scheduled_event('commonsbooking_email_bookingcodes');
		$this->assertIsObject($schedule);
	}

	/* Tests if cron initiated booking codes email is sent and contains codes */
	public function testSendBookingCodesMessage() {
		reset_phpmailer_instance();
		$email = tests_retrieve_phpmailer_instance();

		$this->setCronParams(strtotime("today"));
		BookingCodes::sendBookingCodesMessage();

		$this->assertNotFalse($email->get_sent());
		$this->assertMatchesRegularExpression('/' . implode('|',self::bookingCodes) . '/',$email->get_sent()->body);
	}

	/* As testSendBookingCodesMessage but cover the case when Ical is attached */
	public function testSendBookingCodesMessageWithICal() {

		// enable sending of Ical attachment
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'mail-booking-codes-attach-ical', 'on');

		$this->testSendBookingCodesMessage();
	}

	/* Tests some exceptional calculations for emailing booking codes (range and next event) */
	public function testGetCronParams() {
		$this->setCronParams(strtotime("2020-02-29"),strtotime("2023-01-15"));
		$params=BookingCodes::getCronParams($this->timeframeId);
		$this->assertEquals(date("Y-m-d",$params['nextCronEventTs']),"2023-02-28");
		$this->assertEquals(date("Y-m-d",$params['from']),"2023-02-01");
		$this->assertEquals(date("Y-m-d",$params['to']),"2023-02-28");

		$this->setCronParams(strtotime("2020-02-29"),strtotime("2024-01-15"));
		$params=BookingCodes::getCronParams($this->timeframeId);
		$this->assertEquals(date("Y-m-d",$params['nextCronEventTs']),"2024-02-29");
		$this->assertEquals(date("Y-m-d",$params['from']),"2024-02-01");
		$this->assertEquals(date("Y-m-d",$params['to']),"2024-02-29");

		$this->setCronParams(strtotime("2020-03-31"),strtotime("2024-01-15"),3);
		$params=BookingCodes::getCronParams($this->timeframeId);
		$this->assertEquals(date("Y-m-d",$params['nextCronEventTs']),"2024-04-30");
		$this->assertEquals(date("Y-m-d",$params['from']),"2024-02-01");
		$this->assertEquals(date("Y-m-d",$params['to']),"2024-04-30");


	}

	protected function deleteCBOptions() {
		foreach ( wp_load_alloptions() as $option => $value ) {
			if ( str_starts_with( $option, COMMONSBOOKING_PLUGIN_SLUG . '_options' ) ) {
				delete_option( $option );
			}
		}
	}

	protected function setCronParams($tsStart, $nextCronEmail=null, $numMonth=1, $enabled=true) {

		update_post_meta( $this->timeframeId, \CommonsBooking\View\BookingCodes::CRON_EMAIL_CODES, array(
			'cron-booking-codes-enabled' => $enabled ,
			'cron-email-booking-code-nummonth' => $numMonth,
			'cron-email-booking-code-start' => $tsStart,

		));

		if($nextCronEmail == null ) $nextCronEmail=strtotime("today");
		update_post_meta( $this->timeframeId, \CommonsBooking\View\BookingCodes::NEXT_CRON_EMAIL, $nextCronEmail );
	}

	protected function setUp() : void {
		parent::setUp();
		//set default options for email templates
		\CommonsBooking\Wordpress\Options\AdminOptions::setOptionsDefaultValues();

		//set defined booking codes option
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes', implode(',',self::bookingCodes) );

		$now               = time();
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+' . self::timeframeStart . ' days midnight', $now ),
			strtotime( '+' . self::timeframeEnd . ' days midnight', $now )
		);

		//force save_post action to generate booking codes
		$timeframePost=get_post($this->timeframeId);
		do_action( 'save_post', $this->timeframeId, $timeframePost, true );

		//set Location email
		$timeframe=new Timeframe($this->timeframeId);
		update_post_meta( $timeframe->getLocation()->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_email', 'dummy_email1@nowhere.com, dummy_email2@everywhere.de' );


	}



	protected function tearDown() : void {
		delete_transient(\CommonsBooking\Model\BookingCode::ERROR_TYPE);
		$this->deleteCBOptions();
		parent::tearDown();

	}


}