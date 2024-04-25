<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\BookingMessage;
use CommonsBooking\Settings\Settings;

class BookingMessageTest extends Email_Test_Case
{

	public function testSendMessage() {
		$subject = '{{item:post_title}} | {{location:post_title}}';
		$body = '{{booking:post_title}} | {{user:user_login}}';
		$expectedSubject = self::ITEM_NAME . ' | ' . self::LOCATION_NAME;
		$expectedBody = self::BOOKING_NAME . ' | ' . self::BOOKINGUSER_USERNAME;
		//set template settings
		Settings::updateOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-confirmed-subject', $subject);
		Settings::updateOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-confirmed-body', $body);

		//enable iCalendar functionality for testing
		Settings::updateOption('commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title', $subject);
		Settings::updateOption('commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-description', $body);
		Settings::updateOption('commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_attach', 'on');

		$bookingMessage = new BookingMessage($this->bookingId, 'confirmed');
		$bookingMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty($mailer->ErrorInfo);
		$this->assertEquals(self::FROM_MAIL, $mailer->From);
		$this->assertEquals(self::FROM_NAME, $mailer->FromName);
		$bcc = $mailer->getBccAddresses();
		$this->assertCount(2, $bcc);
		$this->assertEquals(self::LOCATION_EMAIL, $bcc[0][0]);
		$this->assertEquals(self::SECOND_LOCATION_EMAIL, $bcc[1][0]);

		$this->assertEquals($expectedSubject, $mailer->Subject);
		$this->assertEquals($expectedBody, $mailer->Body);

		$attachment = $mailer->getAttachments();
		//we just make sure it is attached here and that the content is present, file validity is tested in the ICS test
		$this->assertCount(1, $attachment);
		$this->assertStringContainsString($expectedSubject, $attachment[0][0]);
		$this->assertStringContainsString($expectedBody, $attachment[0][0]);
		$this->assertEquals( 'test-booking.ics', $attachment[0][2]);
		$this->assertEquals( 'base64', $attachment[0][3]);
		$this->assertEquals( 'text/calendar', $attachment[0][4]);
		$this->assertEquals( 'attachment', $attachment[0][6]);

    }

	public function setUp(): void {
		parent::setUp();
	}


	public function tearDown(): void {
		parent::tearDown();
	}
}
