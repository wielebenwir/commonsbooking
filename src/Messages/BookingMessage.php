<?php

namespace CommonsBooking\Messages;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Settings\Settings;

class BookingMessage extends Message {

	protected array $validActions = [ "confirmed", "canceled" ];

	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking = Booking::getPostById( $this->getPostId() );

		$booking_user = get_userdata( $this->getPost()->post_author );
		$bcc_adresses = CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_email' ); /*  email adresses, comma-seperated  */

		// get templates from Admin Options
		$template_body    = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-body' );
		$template_subject = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-subject' );


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
			$bcc_adresses,
			[
				'booking'  => $booking,
				'item'     => $booking->getItem(),
				'location' => $booking->getLocation()
			]
		);
		$this->SendNotificationMail();
	}

}