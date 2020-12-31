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
     * @param \WP_Post|null $post
     *
     * @return array
     * @throws \Exception
     */
    public static function getTemplateData(\WP_Post $post = null)
    {
        return [];
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
