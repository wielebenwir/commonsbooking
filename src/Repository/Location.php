<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Location extends PostRepository
{

    /**
     * Returns all published locations.
     * @return array
     * @throws \Exception
     */
    public static function getAllPublished() {
        $items = [];

        $args = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
            'post_status' => array('publish', 'inherit')
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $items = $query->get_posts();
            foreach($items as &$item) {
                $item = new \CommonsBooking\Model\Location($item);
            }
        }
        return $items;
    }

    /**
     * Returns array with locations for item.
     *
     * @param $itemId
     *
     * @return array
     * @throws \Exception
     */
    public static function getByItem($itemId)
    {
        if($itemId instanceof \WP_Post) {
            $itemId = $itemId->ID;
        }
        $locations = [];
        $locationIds = [];

        $args = array(
            'post_type'   => Timeframe::getPostType(),
            'post_status' => array('confirmed', 'unconfirmed', 'publish', 'inherit'),
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'   => 'item-id',
                    'value' => $itemId
                )
            )
        );

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $timeframes = $query->get_posts();
            foreach ($timeframes as $timeframe) {
                $locationId = get_post_meta($timeframe->ID, 'location-id', true);
                if ($locationId && ! in_array($locationId, $locationIds)) {
                    $locationIds[] = $locationId;
                    $location = get_post($locationId);

                    // add only published items
                    if ($location->post_status == 'publish') {
                        $locations[] = $location;
                    }
                }
            }
        }

        foreach ($locations as &$location) {
            $location = new \CommonsBooking\Model\Location($location);
        }

        return $locations;
    }

}
