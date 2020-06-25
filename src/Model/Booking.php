<?php


namespace CommonsBooking\Model;


class Booking extends CustomPost
{

    
    public function booking_timeframe_date()
    {
        $format = get_option('date_format');
        
        $startdate = date($format, get_post_meta($this->post->ID, 'start-date', true));
        $enddate = date($format, get_post_meta($this->post->ID, 'end-date', true));

        if ($startdate == $enddate) {
            return sprintf( esc_html__( ' on %s ' , CB_TEXTDOMAIN), $startdate );
        } else {
            return sprintf( __( ' from %1$s until %2$s ', CB_TEXTDOMAIN ), $startdate, $enddate ) ;
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


    public function booking_link()
    {
        return '<a href="' . site_url('?cb_timeframe=' . $this->post->post_name) . '">' . __( 'Link to your booking', CB_TEXTDOMAIN ) . '</a>';
    }
    

}
