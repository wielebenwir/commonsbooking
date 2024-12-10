<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Service\Scheduler;
use CommonsBooking\Settings\Settings;

/**
 * This message is sent out to users to remind them of their upcoming booking.
 * This is sent using a cron job.
 *
 * @see \CommonsBooking\Service\Scheduler
 */
class BookingReminderMessage extends Message {

	/**
	 * @var array|string[]
	 */
	protected $validActions = array( 'pre-booking-reminder', 'post-booking-notice' );

	/**
	 * Sends reminder message.
	 *
	 * @throws \Exception
	 */
	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking      = Booking::getPostById( $this->getPostId() );
		$booking_user = get_userdata( $this->getPost()->post_author );

		// get templates from Admin Options
		$template_body    = Settings::getOption(
			'commonsbooking_options_reminder',
			$this->action . '-body'
		);
		$template_subject = Settings::getOption(
			'commonsbooking_options_reminder',
			$this->action . '-subject',
			'sanitize_text_field'
		);

		// Setup email: From
		$fromHeaders = sprintf(
			'From: %s <%s>',
			Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-name', 'sanitize_text_field' ),
			sanitize_email( Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) )
		);

		$this->prepareMail(
			MessageRecipient::fromUser( $booking_user ),
			$template_body,
			$template_subject,
			$fromHeaders,
			null,
			array(
				'booking'  => $booking,
				'item'     => $booking->getItem(),
				'location' => $booking->getLocation(),
				'user'     => $booking_user,
			)
		);
		$this->SendNotificationMail();
	}
}
