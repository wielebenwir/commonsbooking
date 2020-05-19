<?php


class CB_Messages
{

    public function __construct()
    {
        
    }

    function SendNotificationMail($to, $subject, $message)
    {

        $this->from = \Settings::GetOption('commonsbooking_email_options', 'email_sender_mail');

        wp_mail($to, $subject, $message, $headers, $attachments);
    }
}
