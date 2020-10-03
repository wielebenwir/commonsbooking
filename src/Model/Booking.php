<?php


namespace CommonsBooking\Model;


use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;

class Booking extends CustomPost
{

    /**
     * Booking states.
     * @var string[]
     */
    public static $bookingStates = [
        "cancelled",
        "confirmed",
        "unconfirmed"
    ];

    /**
     * @return Location
     * @throws \Exception
     */
    public function getLocation() {
        $locationId = self::getMeta('location-id');
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
        $itemId = self::getMeta('item-id');

        if($post = get_post($itemId)) {
            return new Item($post);
        }
        return $post;
    }
    /**
     * @TODO: User
    //  * @return User
    //  * @throws \Exception
    //  */
    // public function getUser() {
    //     $userId = self::getMeta('User-id');

    //     if($post = get_post($userId)) {
    //         return new User($post);
    //     }
    //     return $post;
    // }

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
            $fieldValue = get_post_meta(
                $timeframe->ID,
                $fieldName,
                true
            );
            if(in_array($fieldName, ['start-time', 'end-time'])) {
                $fieldValue = $this->sanitizeTimeField($fieldName);
            }
            update_post_meta(
                $this->post->ID,
                $fieldName,
                $fieldValue
            );
        }
    }

    /**
     * Returns time from repetition-[start/end] field
     * @param $fieldName
     *
     * @return string
     */
    private function sanitizeTimeField($fieldName) {
        $time = new \DateTime();
        $fieldValue = self::getMeta('repetition-start');
        if($fieldName == "end-time") {
            $fieldValue = self::getMeta('repetition-end');
        }
        $time->setTimestamp($fieldValue);
        return $time->format('H:i');
    }

    /**
     * Returns suitable bookable Timeframe for booking.
     * @return mixed
     * @throws \Exception
     */
    public function getBookableTimeFrame() {
        $locationId = self::getMeta('location-id');
        $itemId = self::getMeta('item-id');

        $response = Timeframe::get(
            [$locationId],
            [$itemId],
            [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
            date(CB::getInternalDateFormat(), self::getMeta('repetition-start'))
        );

        if(count($response)) {
            return array_shift($response);
        }
    }

    /**
     * @return string
     */
    public function booking_timeframe_date()
    {
        $date_format = get_option('date_format');
        
        $startdate = date_i18n($date_format, $this->getMeta('repetition-start'));
        $enddate = date_i18n($date_format, $this->getMeta('repetition-end'));

        if ($startdate == $enddate) {
            return sprintf( esc_html__( ' on %s ' , 'commonsbooking'), $startdate );
        } else {
            /* translators: %1 = startdate, %2 = enddate in wordpress defined format */
            return sprintf( __( ' from %1$s until %2$s ', 'commonsbooking' ), $startdate, $enddate ) ;
        }
    }

    
    /**
     * pickupDatetime
     * 
     * renders the pickup date and time information and returns a formatted string
     * this is used in templates/booking-single.php and in email-templates (configuration via admin options)
     * 
     * @return string
     */
    public function pickupDatetime()
    {

        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $date_start = date_i18n($date_format, $this->getMeta('repetition-start'));
        $time_start = date_i18n($time_format, $this->getMeta('repetition-start'));

        $grid = $this->getMeta('grid');
        $full_day = $this->getMeta('full-day');

        if ($full_day == "on") {
            return $date_start;
        }

        if ($grid > 0) { // if grid is set to hourly (grid = 1) or a multiple of an hour
            $time_end = date_i18n($time_format, $this->getMeta('repetition-start') + (60 * 60 * $grid));
        }

        if ($grid == 0) { // if grid is set to slot duration
            $time_end = date_i18n($time_format, strtotime($this->getMeta('end-time')));
        }

        return $date_start . ' ' . $time_start . ' - ' . $time_end;
    }
    
    /**
     * pickupDatetime
     * 
     * renders the return date and time information and returns a formatted string
     * this is used in templates/booking-single.php and in email-templates (configuration via admin options)
     * 
     * @return string
     */

    public function returnDatetime()
    {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        $date_end = date_i18n($date_format, $this->getMeta('repetition-end'));
        $time_end = date_i18n($time_format, $this->getMeta('repetition-end') + 60 ); // we add 60 seconds because internal timestamp is set to hh:59

        $grid = $this->getMeta('grid');
        $full_day = $this->getMeta('full-day');

        if ($full_day == "on") {
            return $date_end;
        }

        if ($grid > 0) { // if grid is set to hourly (grid = 1) or a multiple of an hour
            $time_start = date_i18n($time_format, $this->getMeta('repetition-end') +1 -(60 * 60 * $grid) );
        }

        if ($grid == 0) { // if grid is set to slot duration
            $time_start = date_i18n($time_format, strtotime($this->getMeta('start-time')));
        }

        return $date_end . ' ' . $time_start . ' - ' . $time_end;
    }

    
    /**
     * bookingActionButton
     *
     * @TODO: This calculation should only happen once (it happens twice, for confirm button and cancel button)
     * 
     * @param  mixed $form_action
     * @return void
     */
    public function bookingActionButton($form_action)
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
            include CB_PLUGIN_DIR . 'templates/booking-single-form.php';
        }
    }   
    
    /**
     * show booking notice
     *
     * @return text|html
     */
    public function bookingNotice()
    {
        
        $currentStatus = $this->post->post_status;
        
        if ($currentStatus == "unconfirmed")
        {
            $noticeText = __('Please check your booking and click confirm booking', 'commonsbooking' );
        } else if ($currentStatus == "confirmed")
        {
            $noticeText = __('Your booking is confirmed. A confirmation mail has been sent to you.', 'commonsbooking' );
        }

        if ($currentStatus == "cancelled")
        {
            $noticeText = __('Your booking has been cancelled.', 'commonsbooking' );
        }
        
        return sprintf ('<div class="cb-notice cb-booking-notice cb-status-%s">%s</div>', $currentStatus, $noticeText);

    }

    /**
     * Return HTML Link to booking
     * @TODO: optimize booking link to support different permalink settings or set individual slug (e.g. booking instead of cb_timeframe)
     *
     * @return HTML
     */
    public function bookingLink()
    {
        return sprintf( '<a href="%s">%s</a>', add_query_arg($this->post->post_type, $this->post->post_name, home_url()), __( 'Link to your booking', 'commonsbooking' ) );

    }

}
