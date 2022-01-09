<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Repository\Booking;
use CommonsBooking\Settings\Settings;

class BookingReminderMessage extends Message {

	/**
	 * @var array|string[]
	 */
	protected $validActions = [ "pre-booking-reminder", "post-booking-notice" ];

	/**
	 * Sends reminder message.
	 * @throws \Exception
	 */
	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking = Booking::getPostById( $this->getPostId() );
		$booking_user = get_userdata( $this->getPost()->post_author );

		// get templates from Admin Options
		$template_body    = Settings::getOption( 'commonsbooking_options_reminder',
			$this->action . '-body' );
		$template_subject = Settings::getOption( 'commonsbooking_options_reminder',
			$this->action . '-subject' );

		// Setup email: From
		$fromHeaders = sprintf(
			"From: %s <%s>",
			Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-name' ),
			sanitize_email( Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) )
		);

		$this->prepareMail(
			$booking_user,
			$template_body,
			$template_subject,
			$fromHeaders,
			[],
			[
				'booking'  => $booking,
				'item'     => $booking->getItem(),
				'location' => $booking->getLocation()
			]
		);
		$this->SendNotificationMail();
	}

}