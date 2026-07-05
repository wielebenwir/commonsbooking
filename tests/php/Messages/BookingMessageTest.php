<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\BookingMessage;
use CommonsBooking\Service\BookingPdf;
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

	public function testSendMessage_FilterReceivesSingleAttachmentInHistoricalShape() {
		$filteredAttachment = null;
		$filter             = function ( $attachment ) use ( &$filteredAttachment ) {
			$filteredAttachment = $attachment;
			return $attachment;
		};
		add_filter( 'commonsbooking_mail_attachment', $filter );

		$this->bookingMessage->sendMessage();

		remove_filter( 'commonsbooking_mail_attachment', $filter );

		$this->assertIsArray( $filteredAttachment );
		$this->assertArrayHasKey( 'filename', $filteredAttachment );
		$this->assertEquals( 'test-booking.ics', $filteredAttachment['filename'] );
	}

	public function testSendMessage_WithPdfAttachment() {
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_TEMPLATE, '<h1>{{booking:post_title}}</h1><p>{{item:post_title}}</p>' );
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_ATTACH, 'on' );

		$filteredAttachment = null;
		$filter             = function ( $attachment ) use ( &$filteredAttachment ) {
			$filteredAttachment = $attachment;
			return $attachment;
		};
		add_filter( 'commonsbooking_mail_attachment', $filter );

		$this->bookingMessage->sendMessage();

		remove_filter( 'commonsbooking_mail_attachment', $filter );

		$this->assertIsArray( $filteredAttachment );
		$this->assertCount( 2, $filteredAttachment );
		$this->assertEquals( 'test-booking.ics', $filteredAttachment[0]['filename'] );
		$this->assertEquals( 'application/pdf', $filteredAttachment[1]['type'] );

		$attachments = $this->getMockMailer()->getAttachments();
		$this->assertCount( 2, $attachments );

		$pdfAttachment = $this->getAttachmentByType( $attachments, 'application/pdf' );
		$this->assertNotNull( $pdfAttachment );
		$this->assertStringStartsWith( '%PDF-', $pdfAttachment[0] );
		$this->assertEquals( 'Rental-form-test-booking.pdf', $pdfAttachment[2] );
		$this->assertEquals( 'base64', $pdfAttachment[3] );
		$this->assertEquals( 'application/pdf', $pdfAttachment[4] );
		$this->assertEquals( 'attachment', $pdfAttachment[6] );
	}

	public function testSendMessage_CanceledDoesNotAttachPdf() {
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking-canceled-subject', 'Canceled {{item:post_title}}' );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking-canceled-body', 'Canceled {{booking:post_title}}' );
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_TEMPLATE, '<h1>{{booking:post_title}}</h1>' );
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_ATTACH, 'on' );

		$bookingMessage = new BookingMessage( $this->bookingId, 'canceled' );
		$bookingMessage->sendMessage();

		$attachments = $this->getMockMailer()->getAttachments();
		$this->assertCount( 1, $attachments );
		$this->assertNull( $this->getAttachmentByType( $attachments, 'application/pdf' ) );
		$this->assertNotNull( $this->getAttachmentByType( $attachments, 'text/calendar' ) );
	}

	public function testDefaultPdfTemplateIsRendered() {
		$template = BookingPdf::getDefaultTemplate();

		$this->assertStringContainsString( 'Rental form', $template );
		$this->assertStringContainsString( 'width: 100%;', $template );
		$this->assertStringNotContainsString( '%1$s', $template );
		$this->assertStringNotContainsString( '100%%', $template );
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
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_TEMPLATE, '' );
		Settings::updateOption( 'commonsbooking_options_templates', BookingPdf::OPTION_ATTACH, '' );

		$this->bookingMessage = new BookingMessage( $this->bookingId, 'confirmed' );
	}

	private function getAttachmentByType( array $attachments, string $type ): ?array {
		foreach ( $attachments as $attachment ) {
			if ( $attachment[4] === $type ) {
				return $attachment;
			}
		}

		return null;
	}
}
