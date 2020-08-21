<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends View
{

    protected static $template = 'item/index.html.twig';

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
            'item' => $item,
            'postUrl' => get_permalink($item),
            'type' => Timeframe::BOOKING_ID
        ];

        $location = isset($_GET['location']) && $_GET['location'] != "" ? $_GET['location'] : false;
        $locations = \CommonsBooking\Repository\Location::getByItem($item->ID);

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
            $args['location'] = get_post($location);
        }

        return $args;
    }

    public static function index(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }
        $weekNr = isset($_GET['cw']) ? $_GET['cw'] : date('W');
        $week = new Week(date('Y'), $weekNr);
        $lastWeek = new Week($weekNr + 5);

        $item = $post->ID;
        $location = isset($_GET['location']) && $_GET['location'] != "" ? $_GET['location'] : null;
        $type = isset($_GET['type']) && $_GET['type'] != "" ? $_GET['type'] : null;

        echo self::render(self::$template, [
            'post' => $post,
            'wp_nonce' => Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'currentLocation' => $location,
            'currentItem' => $item,
            'currentType' => $type,
            'items' => \CommonsBooking\Wordpress\CustomPostType\Item::getAllPosts(),
            'types' => Timeframe::getTypes(),
            'calendar' => new Calendar(
                $week->getDays()[0],
                $lastWeek->getDays()[6],
                $location ? [$location] : [],
                $item ? [$item] : [],
                $type ? [$type] : []
            )
        ]);
    }

    /**
     * @param $atts
     * @param null $content
     *
     * @return false|string
     * @throws \Exception
     */
    public static function listItems($atts, $content = null) {
        $templateData['items'] = [];
        if(is_array($atts) && array_key_exists('location-id', $atts)) {
            $templateData['items'] = \CommonsBooking\Repository\Item::getByLocation($atts['location-id']);
        } else {
            $templateData['items'] = \CommonsBooking\Repository\Item::getAllPublished();
        }

        if(count($templateData['items'])) {
            ob_start();
            include CB_PLUGIN_DIR . 'templates/item-list.php';
            return ob_get_clean();
        } else {
            return 'No items for location found..';
        }
    }
}
