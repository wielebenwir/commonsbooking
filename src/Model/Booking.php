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
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $startdate = date($date_format, $this->get_meta('start-date'));
        $enddate = date($date_format, $this->get_meta('end-date'));

        if ($startdate == $enddate) {
            return sprintf( esc_html__( ' on %s ' , 'commonsbooking'), $startdate );
        } else {
            /* translators: %1 = startdate, %2 = enddate in wordpress defined format */
            return sprintf( __( ' from %1$s until %2$s ', 'commonsbooking' ), $startdate, $enddate ) ;
        }
    }


    function render_pickupreturn($action) {

        if ($action == "pickup") {
            $date_type = "start";
        } elseif ($action == "return") {
            $date_type = "end";
        } else {
            return false;
        }

        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $date = date_i18n($date_format, $this->get_meta($date_type .'-date'));
        $time_start = date_i18n($time_format, $this->get_meta($date_type . '-date'));

        $grid = $this->get_meta('grid');
        $full_day = $this->get_meta('full-day');


        if ($full_day == "on") {
            return $date;
        }

        if ($grid > 0) {
            $time_end = date($time_format, $this->get_meta($date_type . '-date') + (60 * 60 * $grid));
        }

        if ($grid == 0) { // if grid is set to slot duration
            $time_end = date($time_format, $this->get_meta($date_type . '-date'));
        }

        return $date . ' ' . $time_start . ' - ' . $time_end;

    }
    
    
    
    /**
     * pickup_datetime
     *
     * @return void
     */
    public function pickup_datetime()
    {

        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $date_start = date_i18n($date_format, $this->get_meta('start-date'));
        $time_start = date_i18n($time_format, $this->get_meta('start-date'));

        $grid = $this->get_meta('grid');
        $full_day = $this->get_meta('full-day');

        if ($full_day == "on") {
            return $date_start;
        }

        if ($grid > 0) { // if bookable grid is set to hour
            $time_end = date_i18n($time_format, $this->get_meta('start-date') + (60 * 60 * $grid));
        }

        if ($grid == 0) { // if grid is set to slot duration
            $time_end = date_i18n($time_format, strtotime($this->get_meta('end-time')));
        }

        return $date_start . ' ' . $time_start . ' - ' . $time_end;;
    }
    
    /**
     * return_datetime
     *
     * @return void
     */
    public function return_datetime()
    {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $date_end = date_i18n($date_format, $this->get_meta('end-date'));
        $time_end = date_i18n($time_format, $this->get_meta('end-date') + 60 ); // we add 60 seconds because internal timestamp is set to hh:59 

        $grid = $this->get_meta('grid');
        $full_day = $this->get_meta('full-day');

        if ($full_day == "on") {
            return $date_end;
        }

        if ($grid > 0) {
            $time_start = date_i18n($time_format, $this->get_meta('end-date') +1 -(60 * 60 * $grid) );
        }

        if ($grid == 0) { // if grid is set to slot duration
            $time_start = date_i18n($time_format, strtotime($this->get_meta('start-time')));
        }

        return $date_end . ' ' . $time_start . ' - ' . $time_end;;
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
        }

        If ($current_status == 'unconfirmed' AND $form_action == "confirm") 
        {
            $form_post_status = 'confirmed';
            $button_label = __('Confirm Booking', 'commonsbooking');
        }

        If ($current_status == 'confirmed' AND $form_action == "cancel") 
        {
            $form_post_status = 'cancelled';
            $button_label = __('Cancel Booking', 'commonsbooking');
        }

        if (isset($form_post_status)) {       
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
            return __('Your booking is confirmed. A confirmation mail has been sent to you. <br> Enjoy your cargo bike trip :-)', 'commonsbooking' );
        }

        if ($current_status == "cancelled")
        {
            return __('Your booking has been cancelled.', 'commonsbooking' );
        }

    }

}
