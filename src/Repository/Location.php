<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Location extends PostRepository
{

    /**
     * Returns an array of CB location post objects
     * 
     * @param $args WP Post args
     * @return array
     */
    public static function get($args = array()) {
        
        $args['post_type'] =  \CommonsBooking\Wordpress\CustomPostType\Location::getPostType();
             
        $defaults = array(
            'post_status' => array('publish', 'inherit'),
        );

        $queryArgs = wp_parse_args($args, $defaults);
        $query = new \WP_Query($queryArgs);

        if ($query->have_posts()) {
            $locations = $query->get_posts();
            foreach($locations as &$location) {
                $location = new \CommonsBooking\Model\Location($location);
            }
        }
        return $locations;
    }
    /**
     * Returns all published locations.
     * @return array
     * @throws \Exception
     */
    public static function getAllPublished() {
        $locations = [];

        $args = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
            'post_status' => array('publish', 'inherit')
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $locations = $query->get_posts();
            foreach($locations as &$item) {
                $item = new \CommonsBooking\Model\Location($item);
            }
        }
        return $locations;
    }

    /**
     * Get all Locations current user is allowed to see/edit
     * @return array
     */
    public static function getByCurrentUser() {
        $current_user = wp_get_current_user();
        $locations = [];

        // Get all Locations where current user is author
        $args = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
            'author' => $current_user->ID
        );
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $locations = array_merge($locations, $query->get_posts());
        }

        // get all locations where current user is assigned as admin
        $args = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'   => '_' . \CommonsBooking\Wordpress\CustomPostType\Location::$postType . '_admins',
                    'value' => '"' . $current_user->ID . '"',
                    'compare' => 'like'
                )
            )
        );
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $locations = array_merge($locations, $query->get_posts());
        }

        return $locations;
    }

    /**
     * Returns array with locations for item.
     *
     * @param $itemId
     *
     * @param bool $bookable
     *
     * @return array
     * @throws \Exception
     */
    public static function getByItem($itemId, $bookable = false)
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

        foreach ($locations as $key => &$location) {
            $location = new \CommonsBooking\Model\Location($location);
            if($bookable && !$location->getBookableTimeframesByItem($itemId)) {
                unset($locations[$key]);
            }
        }

        return $locations;
    }

}
