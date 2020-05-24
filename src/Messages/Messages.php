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

        $this->from = Settings::getOption('commonsbooking_email_options', 'email_sender_mail');


        wp_mail($to, $subject, $message, $headers, $attachments);
    }
}
