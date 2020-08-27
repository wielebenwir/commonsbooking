<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;

class Location extends CustomPost
{
    /**
     * Returns bookable timeframes for location.
     * 
     * @TODO: should support $args 
     *
     * @return array
     */
    public function getBookableTimeframes()
    {
        return Timeframe::get([$this->ID], [], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID], NULL, TRUE);
    }

    /**
     * Returns location infos.
     * @return string
     */    
    /**
     * location_address
     * 
     * @TODO: retire. should be: address()
     * @TODO: Use WP standard geo meta. geo_postcode, etc. 
     *
     * @return void
     */
    public function location_address()
    {
        
        $location = array (
            CB::get('location', 'name', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_street', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_city', $this->post->ID),
        );

        return implode('<br>', $location);

    }
    /**
     * 
     * @return string
     */
    public function address( )
    {
        return sprintf(
            '%s, %s %s',
            CB::get('location', CB_METABOX_PREFIX . 'location_street'),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode'),
            CB::get('location', CB_METABOX_PREFIX . 'location_city')
        );

    }

    /**
     * Returns location contact info.
     * @return string
     */
    public function location_contact()
    {   
        if ( !empty( CB::get( 'location', CB_METABOX_PREFIX . 'location_contact') ) ) {
            $contact[] = "<br>"; // needed for email template
            $contact[] = __( 'Please contact the contact persons at the location directly if you have any questions regarding collection or return:', 'commonsbooking' );
            $contact[] = nl2br(CB::get('location',  CB_METABOX_PREFIX . 'location_contact'));
        }

        return implode('<br>', $contact);

    }

}
