<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends PostRepository
{
    
    /**
     * Returns array with items at location.
     * @param $locationId
     * @return array
     */
    public static function getByLocation($locationId) {
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
                $result = get_post_meta($timeframe->ID, 'item-id');
                $itemId = false;
                if(count($result)) {
                    $itemId = $result[0];
                }

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

}
