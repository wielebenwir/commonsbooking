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

        $startSlot = isset($_GET['start']['slot'])  && $_GET['start']['slot'] != "" ? $_GET['start']['slot'] : null;
        $startDate = isset($_GET['start']['date'])  && $_GET['start']['date'] != "" ? $_GET['start']['date'] : null;

        $endSlot = isset($_GET['end']['slot'])  && $_GET['end']['slot'] != "" ? $_GET['end']['slot'] : null;
        $endDate = isset($_GET['end']['date'])  && $_GET['end']['date'] != "" ? $_GET['end']['date'] : null;

        $timeformat = 'd.m.Y';
        $bookingStartDay = new Day($startDate, [$location],[$item]);
        $startSlotInfo = $bookingStartDay->getSlot($startSlot);

        $bookingEndDay = new Day($endDate, [$location],[$item]);
        $endSlotInfo = $bookingStartDay->getSlot($endSlot);

        echo self::render(self::$template, [
            'post' => $post,
            'wp_nonce' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'startSlot' => $startSlot,
            'endSlot' => $endSlot,
            'start_date_string' => $bookingStartDay->getFormattedDate($timeformat) . ' ' . $startSlotInfo['timestart'],
            'end_date_string' => $bookingEndDay->getFormattedDate($timeformat) . ' ' . $endSlotInfo['timeend'],
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
            'startDate' => $bookingStartDay->getFormattedSlotStartDate('Y-m-d\TH:i', $startSlot),
            'endDate' => $bookingEndDay->getFormattedSlotEndDate('Y-m-d\TH:i', $endSlot)
        ]);
    }

}
