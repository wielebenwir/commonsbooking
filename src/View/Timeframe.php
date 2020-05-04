<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Day;

class Timeframe extends View
{

    protected static $template = 'timeframe/index.html.twig';

    public static function content(\WP_Post $post)
    {
        \CommonsBooking\Wordpress\CustomPostType\Timeframe::handleFormRequest();

        $itemId = get_post_meta($post->ID, 'item-id', true);
        $locationId = get_post_meta($post->ID, 'location-id', true);

        $item = get_post($itemId);
        $location = get_post($locationId);

        $slot = isset($_GET['slot'])  && $_GET['slot'] != "" ? $_GET['slot'] : null;
        $date = isset($_GET['date'])  && $_GET['date'] != "" ? $_GET['date'] : null;

        $timeformat = 'd.m.Y';
        $bookingDay = new Day($date, [$location],[$item]);
        $slotInfo = $bookingDay->getSlot($slot);

        echo self::render(self::$template, [
            'post' => $post,
            'wp_nonce' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'slot' => $slot,
            'start_date_string' => $bookingDay->getFormattedDate($timeformat) . ' ' . $slotInfo['timestart'],
            'end_date_string' => $slotInfo['timeend'],
            'location' => array(
                'post' => $location,
                'thumbnail' => get_the_post_thumbnail( $location->ID, 'thumbnail' )
            ),
            'item' =>  array(
                'post' => $item,
                'thumbnail' => get_the_post_thumbnail( $item->ID, 'thumbnail' )
            ),
            'user' => wp_get_current_user(),
            'type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID, // statically for bookings, when it works we make it dynamic
            'startDate' => $bookingDay->getFormattedSlotStartDate('Y-m-d\TH:i', $slot),
            'endDate' => $bookingDay->getFormattedSlotEndDate('Y-m-d\TH:i', $slot)
        ]);
    }

}
