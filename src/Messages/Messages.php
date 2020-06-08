<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Settings\Settings;

class CB_Messages
{

    public function __construct()
    {
        
    }

    function SendNotificationMail($to, $subject, $message)
    {
        $to = "mail@cwenzel.de";
        $subject = "Test";
        $message = Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-pending-body');
        $message = cb_parse_template($message);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $this->from = Settings::getOption('commonsbooking_email_options', 'email_sender_mail');

        $subject = Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-pending-subject');
    
  
        $result = \wp_mail($to, $subject, $message, $headers);
        die();
        return $result;
   }
    
}
