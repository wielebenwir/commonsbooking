<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;

class Location extends CustomPost
{

    public function location_information()
    {
        
        $location_info = array (
            get_post_meta($this->post->ID, 'post_title', true),
            get_post_meta($this->post->ID, 'post_title', true),
        );
        
        $date = get_post_meta($this->post->ID, 'end-date', true);

        // TODO format pickup string on fullday-booking // we need slot duration or timestart and time-end for pickup and return
        $format = get_option('date_format'). ' ' . get_option('time_format');
        return date($format, $date);
    }
}
