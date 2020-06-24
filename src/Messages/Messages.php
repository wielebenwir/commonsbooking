<?php

namespace CommonsBooking\Messages;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Shortcodes\Shortcodes;

class Messages
{

	public $postID;
	public $action;

    public function __construct($postId, $action)
    {
		$this->postId = $postId;
		$this->action = $action; 
		$this->cb_object = get_post($this->postId);
    }

    public function triggerMail() {

        if ($this->action == "confirmed") {
			return $this->sendMessage();
		}

		if ($this->action == "cancelled") {
			return $this->sendMessage();
		}

    }

    public function sendMessage() {
        
        $this->prepareMail();
		$this->SendNotificationMail();
    }


    /**
	 * Setup the email template, headers (BCC)
	 */
	public function prepareMail() {

		// Setup email: Recipent
		$booking_user = get_userdata($this->cb_object->post_author);
		$this->to = sprintf('%s <%s>', $booking_user->user_nicename, $booking_user->user_email);

		// WPML: Switch system language to user´s set lang https://wpml.org/documentation/support/sending-emails-with-wpml/
		do_action('wpml_switch_language_for_email', $booking_user->user_email );

		// get templates from Admin Options 
		$template_body 		= Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-' . $this->action . '-body');
		$template_subject 	= Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-' . $this->action . '-subject');

		// parse templates & replaces template tags (e.g. {{item:name}})
		$this->body 	= cb_parse_template($template_body);
		$this->subject 	= cb_parse_template($template_subject);

		// Setup mime type
		$this->headers[] = "MIME-Version: 1.0";
		$this->headers[] = "Content-Type: text/html";

		// Setup email: From
		$this->headers[] = sprintf(
			"From: %s <%s>",
			'cb', //Settings::getOption( 'emailheaders_from-name'),
			'mail@cb.local' //sanitize_email ( Settings::getOption( 'emailheaders_from-email') )
		);

		// TODO: @christian: Add later 
		//Check settings for additionitional Recipients
		// $bcc_roles    = CB2_Settings::get( 'bookingemails_bcc-roles' ); /* WP roles that should recieve the email */
		// $bcc_adresses = CB2_Settings::get( 'bookingemails_bcc-adresses' ); /*  email adresses, comma-seperated  */

		// TODO: @christian: add later - we have to implement user reference in location and item first (cmb2 issue user select)
		// Get users
		// $location_owner_user 	= get_userdata( $this->cb2_object->location->post_author );
		// $item_owner_user 		= get_userdata( $this->cb2_object->item->post_author );

		// if ( is_array( $bcc_roles ) ) {
		// 	if ( in_array ( 'admin-bcc', $bcc_roles ) )  { $this->add_bcc ( get_bloginfo('admin_email') ); }
		// 	if ( in_array ( 'item-owner-bcc', $bcc_roles )) { $this->add_bcc ( $item_owner_user->user_email ); }
		// 	if ( in_array ( 'location-owner-bcc', $bcc_roles )) { $this->add_bcc ( $location_owner_user->user_email ); }
		// }

		// if (! empty ( $bcc_adresses ) ) {
		// 	$adresses_array = explode ( ',', $bcc_adresses );
		// 	foreach ( $adresses_array as $adress ) {
		// 		$this->add_bcc( $adress );
		// 	}
		// }
	}

	public function add_bcc( $adress ) {
		if (! in_array ( $adress, $this->headers ) ) { // prevent double emails, e.g. if admin=item owner
			$this->headers[] = sprintf("Bcc:%s", sanitize_email( $adress ));
		}
	}


	/**
	 * Send the email
	 */
	public function SendNotificationMail() {

		$to 	 = apply_filters( 'cb2_mail_to', $this->to );
		$subject = apply_filters( 'cb2_mail_subject', $this->subject );
		$body 	 = apply_filters( 'cb2_mail_body', $this->body );
		$headers = implode("\r\n", $this->headers);
	
		$result = false;
		$result = wp_mail($to, $subject, $body, $headers);

		// WPML: Reset system lang
		do_action('wpml_reset_language_after_mailing');
		
		do_action( 'cb2_mail_sent', $this->action, $result );

	}


	/**
	 * Print mail debug
	 */
	function cb_mail_error_debug($wp_error){
		echo "<pre>";
		print_r($wp_error);
		echo "</pre>";
	}

}
