<?php

namespace CommonsBooking\Messages;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Location;

/**
 * This message is sent out to locations to remind them of bookings starting soon or ending soon.
 * This is sent using a cron job.
 *
 * @see \CommonsBooking\Service\Scheduler
 */
class LocationBookingReminderMessage extends Message {

	/**
	 * @var array|string[]
	 */
	protected $validActions = [ 'booking-start-location-reminder', 'booking-end-location-reminder' ];

	/**
	 * Sends reminder message.
	 */
	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking      = Booking::getPostById( $this->getPostId() );
		$booking_user = get_userdata( $this->getPost()->post_author );

		// get location email adresses to send them bcc copies
		$location               = get_post( $booking->getMeta( 'location-id' ) );
		$location_emails_option = CB::get( Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $location ); /*  email addresses, comma-seperated  */
		if ( empty( $location_emails_option ) ) {
			return;
		}

		$location_emails_option = str_replace( ' ', '', $location_emails_option );
		$location_emails        = explode( ',', $location_emails_option );

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

		if ( ! is_array( $location_emails ) ) {
			return;
		}

		$recipientUser = new MessageRecipient( array_shift( $location_emails ), $booking->getLocation()->post_title );
		$bcc_adresses  = implode( ',', $location_emails );

		$this->prepareMail(
			$recipientUser,
			$template_body,
			$template_subject,
			$fromHeaders,
			$bcc_adresses,
			[
				'booking'  => $booking,
				'item'     => $booking->getItem(),
				'location' => $booking->getLocation(),
				'user'     => $booking_user,
			]
		);

		$sendMessage = apply_filters( 'commonsbooking_before_send_location_reminder_mail', $this );
		if ( $sendMessage ) {
			$this->sendNotificationMail();
		}
	}
}
