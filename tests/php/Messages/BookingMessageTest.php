<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\BookingMessage;
use CommonsBooking\Settings\Settings;

class BookingMessageTest extends Email_Test_Case {

	protected BookingMessage $bookingMessage;
	protected string $expectedSubject;
	protected string $expectedBody;

	public function testSendMessage() {
		$this->bookingMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$bcc = $mailer->getBccAddresses();
		$this->assertCount( 2, $bcc );
		$this->assertEquals( self::LOCATION_EMAIL, $bcc[0][0] );
		$this->assertEquals( self::SECOND_LOCATION_EMAIL, $bcc[1][0] );

		$this->assertEquals( $this->expectedSubject, $mailer->Subject );
		$this->assertEquals( $this->expectedBody, $mailer->Body );

		$attachment = $mailer->getAttachments();
		// we just make sure it is attached here and that the content is present, file validity is tested in the ICS test
		$this->assertCount( 1, $attachment );
		$this->assertStringContainsString( $this->expectedSubject, $attachment[0][0] );
		$this->assertStringContainsString( $this->expectedBody, $attachment[0][0] );
		$this->assertEquals( 'test-booking.ics', $attachment[0][2] );
		$this->assertEquals( 'base64', $attachment[0][3] );
		$this->assertEquals( 'text/calendar', $attachment[0][4] );
		$this->assertEquals( 'attachment', $attachment[0][6] );
	}

	public function testSendMessage_BCCDisabled() {
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc', '' );
		$this->bookingMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$bcc = $mailer->getBccAddresses();
		$this->assertEmpty( $bcc );
	}

	public function setUp(): void {
		parent::setUp();
		$subject = '{{item:post_title}} | {{location:post_title}}';
		$body    = '{{booking:post_title}} | {{user:user_login}}';

		$this->expectedSubject = self::ITEM_NAME . ' | ' . self::LOCATION_NAME;
		$this->expectedBody    = self::BOOKING_NAME . ' | ' . self::BOOKINGUSER_USERNAME;

		// set template settings
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking-confirmed-subject', $subject );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking-confirmed-body', $body );

		// enable iCalendar functionality for testing
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title', $subject );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-description', $body );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_attach', 'on' );

		$this->bookingMessage = new BookingMessage( $this->bookingId, 'confirmed' );
	}


	public function tearDown(): void {
		parent::tearDown();
	}
}
