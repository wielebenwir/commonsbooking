<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;

class Location extends CustomPost
{
    /**
     * Returns bookable timeframes for location.
     * @return array
     */
    public function getBookableTimeframes()
    {
        return Timeframe::get([$this->ID], [], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID]);
    }

    /**
     * Returns location infos.
     * @return string
     */    
    /**
     * location_address
     *
     * @return void
     */
    public function location_address()
    {
        
        $location = array (
            CB::get('location', CB_METABOX_PREFIX . 'location_street', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_city', $this->post->ID),
        );

        return implode('<br>', $location);

    }

    /**
     * Returns location contact info.
     * @return string
     */
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
