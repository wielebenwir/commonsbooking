<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;

class Location extends CustomPost
{

    public function location_information()
    {
        
        $location = array (
            get_the_title($this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_street'),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode'),
            CB::get('location', CB_METABOX_PREFIX . 'location_city'),
        );

        return implode('<br>', $location);

    }

    public function location_contact()
    {   
        if ( !empty( CB::get( 'location', CB_METABOX_PREFIX . 'location_contact') ) ) {
            $contact[] = "<br>"; // needed for email template
            $contact[] = __( 'Please contact the contact persons at the location directly if you have any questions regarding collection or return:', CB_TEXTDOMAIN );
            $contact[] = nl2br(CB::get('location',  CB_METABOX_PREFIX . 'location_contact'));
        }

        return implode('<br>', $contact);

    }
}
