<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\BookingReminderMessage;
use CommonsBooking\Settings\Settings;

class BookingReminderMessageTest extends Email_Test_Case {


	public function testSendMessage() {
		$subject         = '{{item:post_title}} | {{location:post_title}}';
		$body            = '{{booking:post_title}} | {{user:user_login}}';
		$expectedSubject = self::ITEM_NAME . ' | ' . self::LOCATION_NAME;
		$expectedBody    = self::BOOKING_NAME . ' | ' . self::BOOKINGUSER_USERNAME;
		// set template settings
		Settings::updateOption( 'commonsbooking_options_reminder', 'pre-booking-reminder-subject', $subject );
		Settings::updateOption( 'commonsbooking_options_reminder', 'pre-booking-reminder-body', $body );

		$bookingMessage = new BookingReminderMessage( $this->bookingId, 'pre-booking-reminder' );
		$bookingMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$this->assertEmpty( $mailer->getBccAddresses() );
		$this->assertEquals( $expectedSubject, $mailer->Subject );
		$this->assertEquals( $expectedBody, $mailer->Body );
	}
}
