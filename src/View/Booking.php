<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Booking extends View
{

    /**
     * Returns template data for frontend.
     *
     *
     * @return array
     * @throws \Exception
     */
    public static function getTemplateData()
    {
        $limit = sanitize_text_field($_POST['limit']);
        $offset = sanitize_text_field($_POST['offset']);
        $order = sanitize_text_field($_POST['order']);
        $search = sanitize_text_field($_POST['search']);

        $bookingData = \CommonsBooking\Repository\Booking::getForCurrentUser($offset);
        $bookingDataArray = [];

        foreach ($bookingData['rows'] as $booking) {
            $userInfo = get_userdata($booking->post_author);
            $editLink = get_permalink($booking->ID);
            if(commonsbooking_isCurrentUserAdmin()) {
                $editLink = get_edit_post_link($booking->ID);
            }
            $actions = '<a href="' . $editLink . '">'.__('editieren', COMMONSBOOKING_PLUGIN_SLUG).'</a>';

            $bookingDataArray['rows'][] = [
                "startDate"   => date('d.m.Y H:i', $booking->getStartDate()),
                "endDate"     => date('d.m.Y H:i', $booking->getStartDate()),
                "item"        => $booking->getItem()->title(),
                "location"    => $booking->getLocation()->title(),
                "bookingDate" => date('d.m.Y H:i', strtotime($booking->post_date)),
                "user"        => $userInfo->user_login,
                "status"      => $booking->post_status,
                "actions"     => $actions
            ];
        }

        $bookingDataArray['total'] = $bookingData['total'];

        header('Content-Type: application/json');
        echo json_encode($bookingDataArray);
        wp_die(); // All ajax handlers die when finished
    }

    /**
     * cb_items shortcode
     *
     * A list of items with timeframes.
     *
     * @param $atts
     *
     * @return false|string
     * @throws \Exception
     */
    public static function shortcode($atts)
    {
        global $templateData;
        $templateData = [];
        $templateData['bookings'] = \CommonsBooking\Repository\Booking::getForCurrentUser();

        ob_start();
        commonsbooking_get_template_part(
            'shortcode',
            'bookings',
            true,
            false,
            false
        );
        return ob_get_clean();
    }
}
