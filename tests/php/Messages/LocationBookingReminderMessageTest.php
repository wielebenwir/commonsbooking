<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\LocationBookingReminderMessage;
use CommonsBooking\Settings\Settings;

class LocationBookingReminderMessageTest extends Email_Test_Case {

	public function testSendMessage() {
		$startSubject         = 'Start: {{item:post_title}} | {{location:post_title}}';
		$startBody            = 'Start: {{booking:post_title}} | {{user:user_login}}';
		$startExpectedSubject = 'Start: ' . self::ITEM_NAME . ' | ' . self::LOCATION_NAME;
		$startExpectedBody    = 'Start: ' . self::BOOKING_NAME . ' | ' . self::BOOKINGUSER_USERNAME;
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-start-location-reminder-subject', $startSubject );
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-start-location-reminder-body', $startBody );

		$endSubject         = 'End: {{item:post_title}} | {{location:post_title}}';
		$endBody            = 'End: {{booking:post_title}} | {{user:user_login}}';
		$endExpectedSubject = 'End: ' . self::ITEM_NAME . ' | ' . self::LOCATION_NAME;
		$endExpectedBody    = 'End: ' . self::BOOKING_NAME . ' | ' . self::BOOKINGUSER_USERNAME;
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-end-location-reminder-subject', $endSubject );
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-end-location-reminder-body', $endBody );

		// Test start reminder
		$message = new LocationBookingReminderMessage( $this->bookingId, 'booking-start-location-reminder' );
		$message->triggerMail();

		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );

		// Check From
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );

		// Check To (should be location)
		$this->assertEquals(
			[
				[
					self::LOCATION_EMAIL,
					self::LOCATION_NAME,
				],
			],
			$mailer->getToAddresses()
		);

		// Check Bcc (should be the second (and more) e-mail address in the location email list)
		$this->assertEquals( self::SECOND_LOCATION_EMAIL, $mailer->getBccAddresses()[0][0] );

		// Check Subject & body
		$this->assertEquals( $startExpectedSubject, $mailer->Subject );
		$this->assertEquals( $startExpectedBody, $mailer->Body );

		// Reset before next email test
		$this->resetMailer();

		// Test end reminder
		$message = new LocationBookingReminderMessage( $this->bookingId, 'booking-end-location-reminder' );
		$message->triggerMail();
		$mailer = $this->getMockMailer();

		// Only Check subject & body here, the rest is the same as the start reminder
		$this->assertEquals( $endExpectedSubject, $mailer->Subject );
		$this->assertEquals( $endExpectedBody, $mailer->Body );
	}

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}
}
