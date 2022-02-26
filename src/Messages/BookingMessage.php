<?php

namespace CommonsBooking\Messages;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Location;

class BookingMessage extends Message {

	protected $validActions = [ "confirmed", "canceled" ];

	public function sendMessage() {
		/** @var \CommonsBooking\Model\Booking $booking */
		$booking = Booking::getPostById( $this->getPostId() );

		$booking_user = get_userdata( $this->getPost()->post_author );

		// get location email adresses to send them bcc copies 
		$location_emails = CB::get( Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $booking->getMeta('location-id') ) ; /*  email adresses, comma-seperated  */
		$bcc_adresses = str_replace(' ','',$location_emails); 

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
				'location' => $booking->getLocation(),
                'user'     => $booking,
			]
		);
		$this->SendNotificationMail();
	}

}