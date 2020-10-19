<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends View
{

    /**
     * Returns template data for frontend.
     * @param \WP_Post|null $post
     *
     * @return array
     * @throws \Exception
     */
    public static function getTemplateData(\WP_Post $post = null) {
        if ($post == null) {
            global $post;
        }
        $item = $post;
        $args = [
            'post' => $post,
            'wp_nonce' => Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'item' => new \CommonsBooking\Model\Item($item),
            'postUrl' => get_permalink($item),
            'type' => Timeframe::BOOKING_ID,
            'calendar_data' => json_encode(Location::getCalendarDataArray())
        ];

        $location = get_query_var('location')?: false;
        $locations = \CommonsBooking\Repository\Location::getByItem($item->ID, true);


        // If theres no location selected, we'll show all available.
        if (!$location) {
            if (count($locations)) {
                // If there's only one location  available, we'll show it directly.
                if (count($locations) == 1) {
                    $args['location'] = $locations[0];
                } else {
                    $args['locations'] = $locations;
                }
            }
        } else {
            $args['location'] = new \CommonsBooking\Model\Location(get_post($location));
        }

        return $args;
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
        $items = [];
        $queryArgs = shortcode_atts( static::$allowedShortCodeArgs, $atts, \CommonsBooking\Wordpress\CustomPostType\Item::getPostType());

        if(is_array($atts) && array_key_exists('location-id', $atts)) {
            $item = \CommonsBooking\Repository\Item::getByLocation($atts['location-id'], true);
            $items[] = $item;
        } else {
            $items = \CommonsBooking\Repository\Item::get($queryArgs, true);
        }

        ob_start();
        foreach ( $items as $item ) {
            $templateData['item'] = $item;
            cb_get_template_part('shortcode', 'items', TRUE, FALSE, FALSE ); 
        }
        return ob_get_clean();
        
    }
}
