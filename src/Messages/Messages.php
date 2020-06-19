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
        
        
    }


    public function triggerMail() {

        if ($this->action == "confirmed") {
			return $this->sendMessage();
        }

    }

    public function sendMessage() {
        
        //$this->prepareMail();
        return $this->SendNotificationMail();
    }


    /**
	 * Setup the email template, headers (BCC)
	 */
	public function prepareMail() {

		//get Booking-Data


		// Setup email: Recipent
		$booking_user = get_userdata($this->cb2_object->user->ID);
		$this->to = sprintf('%s <%s>', $booking_user->user_nicename, $booking_user->user_email);

		// WPML: Switch system language to user´s set lang https://wpml.org/documentation/support/sending-emails-with-wpml/
		do_action('wpml_switch_language_for_email', $booking_user->user_email );

		// Parse template @TODO: honor timeframe settings
		$template_body 		= CB2_Settings::get('emailtemplates_mail-booking-' . $this->action . '-body');
		$template_subject = CB2_Settings::get('emailtemplates_mail-booking-' . $this->action  . '-subject');

		$this->body 		= CB2::get_the_email( $template_body, $this->cb2_object );
		$this->subject 	= CB2::get_the_email( $template_subject, $this->cb2_object );

		// Setup email: From
		$this->headers[] = sprintf(
			"From: %s <%s>",
			CB2_Settings::get( 'emailheaders_from-name'),
			sanitize_email ( CB2_Settings::get( 'emailheaders_from-email') )
		);


		// Check settings for additionitional Recipients @TODO: honor timeframe settings
		$bcc_roles    = CB2_Settings::get( 'bookingemails_bcc-roles' ); /* WP roles that should recieve the email */
		$bcc_adresses = CB2_Settings::get( 'bookingemails_bcc-adresses' ); /*  email adresses, comma-seperated  */

		// Get users
		$location_owner_user 	= get_userdata( $this->cb2_object->location->post_author );
		$item_owner_user 			= get_userdata( $this->cb2_object->item->post_author );

		if ( is_array( $bcc_roles ) ) {
			if ( in_array ( 'admin-bcc', $bcc_roles ) )  { $this->add_bcc ( get_bloginfo('admin_email') ); }
			if ( in_array ( 'item-owner-bcc', $bcc_roles )) { $this->add_bcc ( $item_owner_user->user_email ); }
			if ( in_array ( 'location-owner-bcc', $bcc_roles )) { $this->add_bcc ( $location_owner_user->user_email ); }
		}

		if (! empty ( $bcc_adresses ) ) {
			$adresses_array = explode ( ',', $bcc_adresses );
			foreach ( $adresses_array as $adress ) {
				$this->add_bcc( $adress );
			}
		}
	}

	public function add_bcc( $adress ) {
		if (! in_array ( $adress, $this->headers ) ) { // prevent double emails, e.g. if admin=item owner
			$this->headers[] = sprintf("Bcc:%s", sanitize_email( $adress ));
		}
	}

    public function SendNotificationMail()
    {
        $to = "mail@cwenzel.de";
        $subject = "Test";
        $message = Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-pending-body');
        $message = nl2br(cb_parse_template($message));
        $headers = array('Content-Type: text/html; charset=UTF-8');

		$from = "test";
		
		$subject = "Test Mail";
		
		var_dump($message);

  
        $result = \wp_mail($to, $subject, $message, $headers);
		return $result;
		exit;
   }
    
}
