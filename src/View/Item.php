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

        //$location = isset($_GET['location']) && $_GET['location'] != "" ? $_GET['location'] : false;
        $location = (get_query_var('location') && get_query_var('location') != "") ? get_query_var('location') : false;
        
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
    * cb_items shortcode
    * 
    * A list of items with timeframes.
    */
    public static function shortcode($atts)
    {
        // @TODO: allowedArgs should be placed in a central place so that all cb post types (e.g. location shortcode can use the same set)
        $allowedArgs= array(
            'p'             => '', // post id
            // Author: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
            'author'        => '',
            'author_name'   => '',
            // Category: https://developer.wordpress.org/reference/classes/wp_query/#category-parameters
            'cat'           => '',
            'cat_name'      => '',
            // Tag: https://developer.wordpress.org/reference/classes/wp_query/#tag-parameters
            'tag'           => '',
            'tag_id'        => '',
            // Status https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
            'post_status'   => '',  
            // Pagination: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
            'posts_per_page'=> '',
            'nopaging'      => '',
            'offset'        => ''
        );

        
        $queryArgs = shortcode_atts( $allowedArgs, $atts, 'cb_items');   
        
        $items = \CommonsBooking\Repository\Item::get($queryArgs);
        
        ob_start();
        foreach ( $items as $item ) {   
            set_query_var( 'item', $item );
            cb_get_template_part('shortcode', 'items', TRUE, FALSE, FALSE ); 
        }
        return ob_get_clean();
        
    }
}
