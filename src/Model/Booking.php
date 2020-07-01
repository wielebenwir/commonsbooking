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
     * Assings relevant meta fields from related bookable timeframe to booking.
     * @throws \Exception
     */
    public function assignBookableTimeframeFields() {
        $timeframe = $this->getBookableTimeFrame();
        $neededMetaFields = [
            "full-day",
            "grid",
            "start-time",
            "end-time"
        ];
        foreach($neededMetaFields as $fieldName) {
            update_post_meta(
                $this->post->ID,
                $fieldName,
                get_post_meta($timeframe->ID,
                    $fieldName,
                    true
                )
            );
        }
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
        
        $startdate = date($format, $this->get_meta('start-date'));
        $enddate = date($format, $this->get_meta('end-date'));

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

    
    /**
     * booking_action_button
     *
     * @param  mixed $form_action
     * @return void
     */
    public function booking_action_button($form_action)
    {
        global $post;
        $booking = new Booking($post->ID); // is used in template booking-action-form.php 
        $current_status = $this->post->post_status;

        // return form with action button based on current booking status and defined form-action

        If ($current_status == 'unconfirmed' AND $form_action == "cancel") 
        {
            $form_post_status = 'cancelled';
            $button_label = __('Cancel', 'commonsbooking');
            include CB_PLUGIN_DIR . 'templates/components/booking-action-form.php';
        }

        If ($current_status == 'unconfirmed' AND $form_action == "confirm") 
        {
            $form_post_status = 'confirmed';
            $button_label = __('Confirm Booking', 'commonsbooking');
            include CB_PLUGIN_DIR . 'templates/components/booking-action-form.php';
        }

        If ($current_status == 'confirmed' AND $form_action == "cancel") 
        {
            $form_post_status = 'cancelled';
            $button_label = __('Cancel Booking', 'commonsbooking');
            include CB_PLUGIN_DIR . 'templates/components/booking-action-form.php';
        }


    }
    
    
    /**
     * show booking notice
     *
     * @return void
     */
    public function booking_notice()
    {
        $current_status = $this->post->post_status;

        if ($current_status == "unconfirmed")
        {
            return __('Please check your booking and click confirm booking', 'commonsbooking' );
        }

        if ($current_status == "confirmed")
        {
            return __('Your booking is confirmed. <br>A confirmation mail has been sent to you. Enjoy your cargo bike trip', 'commonsbooking' );
        }

        if ($current_status == "cancelled")
        {
            return __('Your booking has been cancelled.', 'commonsbooking' );
        }

    }

}
