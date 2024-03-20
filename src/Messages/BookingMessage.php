<?php

namespace CommonsBooking\Messages;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Location;

/**
 * This is the message that is sent out to a booking user when their booking is confirmed or canceled.
 */
class BookingMessage extends Message {

	/**
	 * Booking messages can either notify a user about a confirmed booking or a canceled booking.
	 * @var string[]
	 */
	protected $validActions = [ "confirmed", "canceled" ];

	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking = Booking::getPostById( $this->getPostId() );

		$booking_user = $booking->getUserData();

		$template_objects = [
			'booking'  => $booking,
			'item'     => $booking->getItem(),
			'location' => $booking->getLocation(),
			'user'     => $booking_user,
		];

		// get location email adresses to send them bcc copies
		$location = get_post($booking->getMeta('location-id'));
		$location_emails = CB::get( Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $location ) ; /*  email addresses, comma-seperated  */
		if ($location_emails) {
			$bcc_adresses = str_replace(' ','',$location_emails);
		} else {
			$bcc_adresses = null;
		}

		// get templates from Admin Options
		$template_body    = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-body' );
		$template_subject = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-subject', 'sanitize_text_field' );


		// Setup email: From
		$fromHeaders = sprintf(
			"From: %s <%s>",
			Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-name', 'sanitize_text_field' ),
			sanitize_email( Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) )
		);

		//generate attachment when set in settings and booking is not cancelled
		$attachment = null;
		if ((Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_attach' ) == 'on') && (!$booking->isCancelled() )){
			$eventTitle = Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title' );
			$eventTitle = commonsbooking_sanitizeHTML ( commonsbooking_parse_template ( $eventTitle, $template_objects ) );

			$eventDescription = Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-description' );
			$eventDescription = commonsbooking_sanitizeHTML ( strip_tags ( commonsbooking_parse_template ( $eventDescription, $template_objects ) ) );

			$attachment = [
				'string' => $booking->getiCal($eventTitle,$eventDescription), // String attachment data (required)
				'filename' => $booking->post_name . '.ics', // Name of the attachment (required)
				'encoding' => 'base64', // File encoding (defaults to 'base64')
				'type' => 'text/calendar', // File MIME type (if left unspecified, PHPMailer will try to work it out from the file name)
				'disposition' => 'attachment' // Disposition to use (defaults to 'attachment')
			];
		}

		$this->prepareMail(
			MessageRecipient::fromUser( $booking_user ),
			$template_body,
			$template_subject,
			$fromHeaders,
			$bcc_adresses,
			$template_objects,
			$attachment
		);
		$this->SendNotificationMail();
	}

}