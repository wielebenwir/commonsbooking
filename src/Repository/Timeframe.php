<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

class Timeframe extends PostRepository
{

    /**
     * @param array $locations
     * @param array $items
     * @param array $types
     * @param string|null $date Date-String
     *
     * @param bool $returnAsModel
     *
     * @param null $minTimestamp
     *
     * @param string[] $postStatus
     *
     * @return array
     * @throws \Exception
     */
    public static function get(
        $locations = [],
        $items = [],
        $types = [],
        ?string $date = null,
        $returnAsModel = false,
        $minTimestamp = null,
        $postStatus = ['confirmed', 'unconfirmed', 'publish', 'inherit']
    ) {
        if ( ! count($types)) {
            $types = [
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
            ];
        }

        if (Plugin::getCacheItem()) {
            return Plugin::getCacheItem();
        } else {
            global $wpdb;
            $posts = [];

            // Get Post-IDs considerung types, items and locations
            $postIds = self::getPostIdsByType($types, $items, $locations);
            if ($postIds && count($postIds)) {
                $dateQuery = "";

                // Filter by date
                if ($date && ! $minTimestamp) {
                    $dateQuery = "
                    INNER JOIN wp_postmeta pm4 ON
                        pm4.post_id = pm1.post_id AND
                        pm4.meta_key = 'repetition-start'
                        pm4.meta_value BETWEEN 0 AND " . strtotime($date . 'T23:59') . " 
                    INNER JOIN wp_postmeta pm5 ON
                        pm5.post_id = pm1.post_id AND
                        pm5.meta_key = 'repetition-end' AND
                        pm5.meta_value BETWEEN " . strtotime($date) . " AND 3000000000                        
                ";
                }

                // Filter only from a specific start date.
                if ($minTimestamp) {
                    $dateQuery = "
                    INNER JOIN wp_postmeta pm4 ON
                        pm4.post_id = wp_posts.id AND
                        pm4.meta_key = 'repetition-end' AND
                        pm4.meta_value > " . $minTimestamp . "
                    INNER JOIN wp_postmeta pm5 ON
                        pm5.post_id = wp_posts.id AND
                        pm5.meta_key = 'repetition-start' AND
                        pm5.meta_value <= " . $minTimestamp . "
                ";
                }
                // Complete query
                $query = "
                    SELECT wp_posts.* from wp_posts
                    " . $dateQuery . "
                    WHERE
                        wp_posts.id in (" . implode(",", $postIds) . ") AND
                        wp_posts.post_type = '" . \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType() . "' AND
                        wp_posts.post_status IN ('" . implode("','", $postStatus) . "')
                ";

                $posts = $wpdb->get_results($query, ARRAY_N);
                // Get posts from result
                foreach ($posts as &$post) {
                    $post = get_post($post[0]);
                }
            }

            if ($posts && count($posts)) {
                // If there are locations or items to be filtered, we iterate through
                // query result because wp_query is to slow for meta-querying them.
                if (count($locations) > 1 || count($items) > 1) {
                    $posts = array_filter($posts, function ($post) use ($locations, $items) {
                        $location = intval(get_post_meta($post->ID, 'location-id', true));
                        $item = intval(get_post_meta($post->ID, 'item-id', true));

                        return
                            ( ! $location && ! $item) ||
                            ( ! $location && in_array($item, $items)) ||
                            (in_array($location, $locations) && ! $item) ||
                            ( ! count($locations) && in_array($item, $items)) ||
                            (in_array($location, $locations) && ! count($items)) ||
                            (in_array($location, $locations) && in_array($item, $items));
                    });
                }
            }

            // if returnAsModel == TRUE the result is a timeframe model instead of a wordpress object
            if ($returnAsModel) {
                foreach ($posts as &$post) {
                    $post = new \CommonsBooking\Model\Timeframe($post);
                }
            }

            Plugin::setCacheItem($posts);
            return $posts;
        }
    }

    /**
     * Returns Post-IDs by type(s), item(s), location(s)
     *
     * @param array $types
     * @param array $items
     * @param array $locations
     *
     * @return mixed
     */
    public static function getPostIdsByType($types = [], $items = [], $locations = [])
    {
        if ( ! count($types)) {
            $types = [
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
            ];
        }

        if (Plugin::getCacheItem()) {
            return Plugin::getCacheItem();
        } else {
            global $wpdb;
            $itemQuery = "";

            // Query for item(s)
            if (count($items)) {
                $itemQuery = "
                    INNER JOIN wp_postmeta pm2 ON
                        pm2.post_id = pm1.post_id AND
                        pm2.meta_key = 'item-id' AND
                        pm2.meta_value IN (" . implode(',', $items) . ")
                ";
            }

            // Query for location(s)
            $locationQuery = "";
            if (count($locations)) {
                $locationQuery = "
                    INNER JOIN wp_postmeta pm3 ON
                        pm3.post_id = pm1.post_id AND
                        pm3.meta_key = 'location-id' AND
                        pm3.meta_value IN (" . implode(',', $locations) . ")
                ";
            }

            // Complete query, including types
            $query = "
                SELECT DISTINCT pm1.post_id from wp_postmeta pm1 
                " .
                     $itemQuery .
                     $locationQuery .
                     "   
                 WHERE
                    pm1.meta_key = 'type' AND
	                pm1.meta_value IN (" . implode(',', $types) . ")
            ";

            // Run query
            $posts = $wpdb->get_results(
                $query, ARRAY_N);

            // Get Post-IDs
            foreach ($posts as &$post) {
                $post = $post[0];
            }

            Plugin::setCacheItem($posts, 'getPostIdsByType');

            return $posts;
        }
    }

    /**
     * @param string|null $date Date-String
     * @deprecated
     * @TODO Check NOT EXISTS query part.
     * @return array
     */
    protected static function getTimeRangeQuery(?string $date = null)
    {
        return array(
            'relation' => "OR",
            // Timeframe has any overlap with current day
            array(
                'relation' => "AND",
                array(
                    'key'     => 'repetition-start',
                    'value'   => [
                        0,
                        strtotime($date . 'T23:59')
                    ],
                    'compare' => 'BETWEEN',
                    'type'    => 'numeric'
                ),
                array(
                    'relation' => "OR",
                    array(
                        'key'     => 'repetition-end',
                        'value'   => [
                            strtotime($date),
                            3000000000
                        ],
                        'compare' => 'BETWEEN',
                        'type'    => 'numeric'
                    ),
                    array(
                        'key'     => 'repetition-end',
                        'compare' => 'NOT EXISTS'
                    )
                )
            )
        );
    }

}
