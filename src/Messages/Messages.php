<?php

namespace CommonsBooking\Messages;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Shortcodes\Shortcodes;

class Messages
{

    public function __construct()
    {
        //$this->SendNotificationMail()
        
    }

    public static function SendNotificationMail($to, $subject, $message)
    {
        $to = "mail@cwenzel.de";
        $subject = "Test";
        $message = Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-pending-body');
        $message = cb_parse_template($message);
        $headers = array('Content-Type: text/html; charset=UTF-8');


        $subject = Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking-pending-subject');
    
  
        $result = \wp_mail($to, $subject, $message, $headers);
        die();
        return $result;
   }
    
}
