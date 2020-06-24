<?php


namespace CommonsBooking\Model;


class Booking extends CustomPost
{

    
    public function booking_timeframe_text()
    {
        $format = get_option('date_format');
        
        $startdate = date($format, get_post_meta($this->post->ID, 'start-date', true));
        $enddate = date($format, get_post_meta($this->post->ID, 'end-date', true));

        if ($startdate == $enddate) {
            return __(" on" . $startdate, CB_TEXTDOMAIN);
        } else {
            return __(" from " . $startdate . " until " . $enddate, CB_TEXTDOMAIN);
        }
    }
    
    // TODO: add pickup timeslot (e.g. 1 hour or full slot depending on timeframe setting)
    /**
     * pickup_datetime
     *
     * @return void
     */
    public function pickup_datetime()
    {
        $date = get_post_meta($this->post->ID, 'start-date', true);

        // TODO format pickup string on fullday-booking // we need slot duration or timestart and time-end for pickup and return
        $format = get_option('date_format'). ' ' . get_option('time_format');
        return date($format, $date);
    }
    
    /**
     * return_datetime
     *
     * @return void
     */
    public function return_datetime()
    {
        $date = get_post_meta($this->post->ID, 'end-date', true);

        // TODO format pickup string on fullday-booking // we need slot duration or timestart and time-end for pickup and return
        $format = get_option('date_format'). ' ' . get_option('time_format');
        return date($format, $date);
    }

}
