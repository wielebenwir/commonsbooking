<?php


namespace CommonsBooking\Model;


class Booking extends CustomPost
{

    // TODO: add pickup timeslot (e.g. 1 hour or full slot depending on timeframe setting)
    /**
     * pickup_datetime
     *
     * @return void
     */
    public function pickup_datetime()
    {
        $date = get_post_meta($this->post->ID, 'start-date', true);
        $format = get_option('date_format'). ' ' . get_option('time_format');
        return date($format, $date);
    }

    /**
     * return_datetime
     * TODO: add time
     * @return void
     */
    public function return_datetime()
    {
        $date = get_post_meta($this->post->ID, 'end-date', true);
        $format = get_option('date_format'). ' ' . get_option('time_format');
        return date($format, $date);
    }

}
