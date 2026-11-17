<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class BookingTest extends CustomPostTypeTest {
	private const MAIL_FROM_NAME        = 'siteAdmin';
	private const MAIL_FROM_EMAIL       = 'test@example.com';
	private const LOCATION_EMAIL        = 'location@example.com';
	private const SECOND_LOCATION_EMAIL = 'locationalias@example.com';

	public function testCleanupBookings() {
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed'
		);
		// first, we check if the cleanup will delete our freshly created unconfirmed booking (it should not)
		Booking::cleanupBookings();
		$this->assertNotNull( get_post( $bookingId ) );

		// we make the post 11 minutes old, so that the cleanup function will delete it (the cleanup function only deletes bookings older than 10 minutes)
		wp_update_post(
			[
				'ID'        => $bookingId,
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-11 minutes' ) ),
			]
		);

		// now we run the cleanup function again
		Booking::cleanupBookings();

		// and check if the post is still there
		$this->assertNull( get_post( $bookingId ) );
	}

	public function testSendReminderMessage() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$daysBeforeStart = 3;
		$reminderDay     = strtotime( '+' . $daysBeforeStart . ' days', strtotime( self::CURRENT_DATE ) );
		$bookingId       = $this->createReminderBooking(
			strtotime( 'midnight', $reminderDay ),
			strtotime( '+2 days', strtotime( 'midnight', $reminderDay ) )
		);

		$this->configureReminderMail( 'pre-booking-reminder', 'Reminder for {{item:post_title}}', 'Location: {{location:post_title}}' );
		Settings::updateOption( 'commonsbooking_options_reminder', 'pre-booking-days-before', $daysBeforeStart );

		$this->assertReminderMailSentAndDisabled(
			'pre-booking-reminder',
			fn() => Booking::sendReminderMessage(),
			'Reminder for TestItem',
			'Location: Testlocation',
			$this->getBookingUserAddress( $bookingId )
		);
	}

	public function testSendBookingStartLocationReminderMessage() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$reminderDay = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );
		$this->createReminderBooking(
			strtotime( 'midnight', $reminderDay ),
			strtotime( '+2 days', strtotime( 'midnight', $reminderDay ) )
		);

		// The setting is 1-based: "2" means the reminder is sent one day before the booking starts.
		$this->configureReminderMail( 'booking-start-location-reminder', 'Start reminder for {{item:post_title}}', 'Location: {{location:post_title}}' );
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-start-location-reminder-day', '2' );
		$this->configureLocationReminder( 'start' );

		$this->assertReminderMailSentAndDisabled(
			'booking-start-location-reminder',
			fn() => Booking::sendBookingStartLocationReminderMessage(),
			'Start reminder for TestItem',
			'Location: Testlocation',
			[
				[
					self::LOCATION_EMAIL,
					'Testlocation',
				],
			],
			self::SECOND_LOCATION_EMAIL
		);
	}

	public function testSendBookingEndLocationReminderMessage() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$reminderDay = strtotime( '+1 day', strtotime( self::CURRENT_DATE ) );
		$this->createReminderBooking(
			strtotime( '-1 day', strtotime( 'midnight', $reminderDay ) ),
			strtotime( 'midnight', $reminderDay )
		);

		// The setting is 1-based: "2" means the reminder is sent one day before the booking ends.
		$this->configureReminderMail( 'booking-end-location-reminder', 'End reminder for {{item:post_title}}', 'Location: {{location:post_title}}' );
		Settings::updateOption( 'commonsbooking_options_reminder', 'booking-end-location-reminder-day', '2' );
		$this->configureLocationReminder( 'end' );

		$this->assertReminderMailSentAndDisabled(
			'booking-end-location-reminder',
			fn() => Booking::sendBookingEndLocationReminderMessage(),
			'End reminder for TestItem',
			'Location: Testlocation',
			[
				[
					self::LOCATION_EMAIL,
					'Testlocation',
				],
			],
			self::SECOND_LOCATION_EMAIL
		);
	}

	public function testSendFeedbackMessage() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$endOfYesterday = strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ) - 1;
		$bookingId      = $this->createReminderBooking(
			strtotime( '-2 days', strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ) ),
			$endOfYesterday
		);

		$this->configureReminderMail( 'post-booking-notice', 'Feedback for {{item:post_title}}', 'Location: {{location:post_title}}' );

		$this->assertReminderMailSentAndDisabled(
			'post-booking-notice',
			fn() => Booking::sendFeedbackMessage(),
			'Feedback for TestItem',
			'Location: Testlocation',
			$this->getBookingUserAddress( $bookingId )
		);
	}

	/**
	 * Creates a confirmed booking with the default times used by the reminder tests.
	 */
	private function createReminderBooking( int $startTimestamp, int $endTimestamp ): int {
		return $this->createBooking(
			$this->locationId,
			$this->itemId,
			$startTimestamp,
			$endTimestamp,
			'8:00 AM',
			'12:00 PM',
			'confirmed'
		);
	}

	/**
	 * Configures the reminder option group with activation, templates and sender settings.
	 */
	private function configureReminderMail( string $reminderId, string $subject, string $body ): void {
		Settings::updateOption( 'commonsbooking_options_reminder', $reminderId . '-activate', 'on' );
		Settings::updateOption( 'commonsbooking_options_reminder', $reminderId . '-subject', $subject );
		Settings::updateOption( 'commonsbooking_options_reminder', $reminderId . '-body', $body );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-name', self::MAIL_FROM_NAME );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-email', self::MAIL_FROM_EMAIL );
	}

	/**
	 * Configures the location e-mail addresses and the opt-in for a location reminder type.
	 */
	private function configureLocationReminder( string $type ): void {
		update_post_meta(
			$this->locationId,
			COMMONSBOOKING_METABOX_PREFIX . 'location_email',
			self::LOCATION_EMAIL . ', ' . self::SECOND_LOCATION_EMAIL
		);
		update_post_meta(
			$this->locationId,
			COMMONSBOOKING_METABOX_PREFIX . 'receive_booking_' . $type . '_reminder',
			'on'
		);
	}

	/**
	 * Returns the expected "to" address of the booking user.
	 */
	private function getBookingUserAddress( int $bookingId ): array {
		$bookingUser = get_userdata( (int) get_post( $bookingId )->post_author );

		return [
			[
				$bookingUser->user_email,
				$bookingUser->user_nicename,
			],
		];
	}

	/**
	 * Asserts that the reminder mail is sent and that disabling the reminder stops further mails.
	 */
	private function assertReminderMailSentAndDisabled(
		string $reminderId,
		callable $sendCallback,
		string $expectedSubject,
		string $expectedBody,
		array $expectedTo,
		?string $expectedBcc = null
	): void {
		reset_phpmailer_instance();
		$sendCallback();

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertNotFalse( $mailer );
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( $expectedSubject, $mailer->Subject );
		$this->assertEquals( $expectedBody, $mailer->Body );
		$this->assertEquals( $expectedTo, $mailer->getToAddresses() );

		if ( $expectedBcc !== null ) {
			$bcc = $mailer->getBccAddresses();
			$this->assertCount( 1, $bcc );
			$this->assertEquals( $expectedBcc, $bcc[0][0] );
		}

		Settings::updateOption( 'commonsbooking_options_reminder', $reminderId . '-activate', 'off' );
		reset_phpmailer_instance();
		$sendCallback();

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertNotFalse( $mailer );
		$this->assertEmpty( $mailer->getToAddresses() );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
	}
}
