<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends View
{

    protected static $template = 'item/index.html.twig';

    public static function index(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }
        $weekNr = isset($_GET['cw']) ? $_GET['cw'] : date('W');
        $week = new Week($weekNr);
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
    public static function listLocations($atts, $content = null) {
        if(array_key_exists('item-id', $atts)) {
            $templateData['locations'] = \CommonsBooking\Repository\Location::getByItem($atts['item-id']);
            if(count($templateData['locations'])) {
                ob_start();
                include_once CB_PLUGIN_DIR . 'templates/location-list.php';
                return ob_get_clean();
            } else {
                return 'No Locations for item found..';
            }
        } else {
            return 'Missing attribute item-id...' . var_export($atts, true);
        }
    }
}
