<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;

class Location extends CustomPost
{

    public function location_information()
    {
        
        $location = array (
            get_the_title($this->post->ID),
            get_post_meta($this->post->ID, CB_METABOX_PREFIX . 'location_street', true),
            get_post_meta($this->post->ID, CB_METABOX_PREFIX . 'location_postcode', true),
            get_post_meta($this->post->ID, CB_METABOX_PREFIX . 'location_city', true),
        );

        return implode('<br>', $location);

    }

    public function location_contact()
    {   
        if (!empty(get_post_meta($this->post->ID, CB_METABOX_PREFIX . 'location_contact', true))) {
            $contact[] = "<br>"; // needed for email template
            $contact[] = __( 'Please contact the contact persons at the location directly if you have any questions regarding collection or return:', CB_TEXTDOMAIN );
            $contact[] = nl2br(get_post_meta($this->post->ID, CB_METABOX_PREFIX . 'location_contact', true));
        }

        return implode('<br>', $contact);

    }
}
