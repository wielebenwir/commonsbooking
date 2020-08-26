<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends PostRepository
{

    /**
     * Returns all published items.
     * @param $args
     * @return array
     * @throws \Exception
     */
    public static function getAllPublished($args = array()) {
        
        $items = [];

        $defaults = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
            'post_status' => array('publish', 'inherit')
        );

        $queryArgs = wp_parse_args($args, $defaults);

        $query = new \WP_Query($queryArgs);

        if ($query->have_posts()) {
            $items = $query->get_posts();
            foreach($items as &$item) {
                $item = new \CommonsBooking\Model\Item($item);
            }
        }
        return $items;
    }

     /**
     * Returns an array of CB item post objects
     * 
     * @param $args
     * @return array
     * @throws \Exception
     */
    public static function get($args = array()) {
        
        // $args['post_type'] =  Item::getPostType(); // how do i get this to work????
        $args['post_type'] =  \CommonsBooking\Wordpress\CustomPostType\Item::getPostType(); // how do i get this to work????
             
        $defaults = array(
            'post_status' => array('publish', 'inherit'),
        );

        $queryArgs = wp_parse_args($args, $defaults);
        $query = new \WP_Query($queryArgs);

        if ($query->have_posts()) {
            $items = $query->get_posts();
            foreach($items as &$item) {
                $item = new \CommonsBooking\Model\Item($item);
            }
        }
        return $items;
    }

    /**
     * Returns array with items at location.
     *
     * @param $locationId
     *
     * @return array
     * @throws \Exception
     */
    public static function getByLocation($locationId) {
        if($locationId instanceof \WP_Post) {
            $locationId = $locationId->ID;
        }
        $items = [];
        $itemIds = [];

        $args = array(
            'post_type' => Timeframe::getPostType(),
            'post_status' => array('confirmed', 'unconfirmed', 'publish', 'inherit'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'location-id',
                    'value' => $locationId
                )
            )
        );

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $timeframes = $query->get_posts();
            foreach ($timeframes as $timeframe) {
                $itemId = get_post_meta($timeframe->ID, 'item-id', true);

                if($itemId && !in_array($itemId, $itemIds)) {
                    $itemIds[] = $itemId;
                    $item = get_post($itemId);
                    // add only published items
                    if($item->post_status == 'publish') {
                        $items[] = $item;
                    }
                }
            }
        }

        foreach($items as &$item) {
            $item = new \CommonsBooking\Model\Item($item);
        }

        return $items;
    }

    /**
    * cb_items shortcode
    * 
    * A list of items with timeframes.
    */
    public static function shortcode($atts)
    {
        $itemArgs = array (
            'post_type'    => 'cb_item'
        );

        //@TODO: parse args
        // $atts = shortcode_atts( $args, $atts, 'cb_locations');
        
        $items = \CommonsBooking\Repository\Item::get($itemArgs);

        
        ob_start();
        echo '<div class="cb-content">';
        foreach ( $items as $item ) {           
            setup_postdata($item); // this does not work, post is page not this sub-post
            cb_get_template_part('shortcode', 'items', TRUE, FALSE, FALSE ); 
        }
        echo '</div>';
        return ob_get_clean();
        
    }
}
