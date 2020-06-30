<?php


namespace CommonsBooking\Model;


use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;

class Booking extends CustomPost
{

    /**
     * @return Location
     * @throws \Exception
     */
    public function getLocation() {
        $locationId = self::get_meta('location-id');
        if($post = get_post($locationId)) {
            return new Location($post);
        }
        return $post;
    }

    /**
     * @return Item
     * @throws \Exception
     */
    public function getItem() {
        $itemId = self::get_meta('item-id');

        if($post = get_post($itemId)) {
            return new Item($post);
        }
        return $post;
    }

    /**
     * Returns suitable bookable Timeframe for booking.
     * @return mixed
     * @throws \Exception
     */
    public function getBookableTimeFrame() {
        $locationId = self::get_meta('location-id');
        $itemId = self::get_meta('item-id');

        $response = Timeframe::get(
            [$locationId],
            [$itemId],
            [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
            date(CB::getInternalDateFormat(), self::get_meta('start-date'))
        );

        if(count($response) == 1) {
            return array_shift($response);
        } else {
            throw new \Exception("more than one timeframes found");
        }
    }

    /**
     * @return string
     */
    public function booking_timeframe_date()
    {
        $format = get_option('date_format');
        
        $startdate = date($format, self::get_meta('start-date'));
        $enddate = date($format, self::get_meta('end-date'));

        if ($startdate == $enddate) {
            return sprintf( esc_html__( ' on %s ' , 'commonsbooking'), $startdate );
        } else {
            /* translators: %1 = startdate, %2 = enddate in wordpress defined format */
            return sprintf( __( ' from %1$s until %2$s ', 'commonsbooking' ), $startdate, $enddate ) ;
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
        return '<a href="' . site_url('?cb_timeframe=' . $this->post->post_name) . '">' . __( 'Link to your booking', 'commonsbooking' ) . '</a>';
    }

    public function submitLabel()
    {
        return __('Book', 'commonsbooking'); 
    }

    public function cancelLabel()
    {
        return __('Cancel', 'commonsbooking'); 
    }
    

}
