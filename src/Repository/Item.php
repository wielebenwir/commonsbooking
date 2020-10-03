<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Item extends BookablePost
{

    /**
     * Returns array with items at location.
     *
     * @param $locationId
     *
     * @param bool $bookable
     *
     * @return array
     * @throws \Exception
     */
    public static function getByLocation($locationId, $bookable = false) {
        if($locationId instanceof \WP_Post) {
            $locationId = $locationId->ID;
        }

        if(Plugin::getCacheItem()) {
            return Plugin::getCacheItem();
        } else {
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

            foreach($items as $key => &$item) {
                $item = new \CommonsBooking\Model\Item($item);

                // If items shall be bookable, we need to check...
                if($bookable && !$item->getBookableTimeframesByLocation($locationId)) {
                    unset($items[$key]);
                }
            }
            Plugin::setCacheItem($items);
            return $items;
        }
    }

    /**
     * @return mixed
     */
    protected static function getPostType()
    {
        return \CommonsBooking\Wordpress\CustomPostType\Item::getPostType();
    }

    /**
     * @return string
     */
    protected static function getModelClass()
    {
        return \CommonsBooking\Model\Item::class;
    }
}
