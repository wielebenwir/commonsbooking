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
    ): array
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

        $customId = md5(serialize($types));
        if (Plugin::getCacheItem($customId)) {
            return Plugin::getCacheItem($customId);
        } else {
            global $wpdb;
            $table_postmeta = $wpdb->prefix . 'postmeta';
            $table_posts = $wpdb->prefix . 'posts';

            $posts = [];

            // Get Post-IDs considerung types, items and locations
            $postIds = self::getPostIdsByType($types, $items, $locations);

            if ($postIds && count($postIds)) {
                $dateQuery = "";

                // Filter by date
                if ($date && ! $minTimestamp) {
                    $dateQuery = "
                    INNER JOIN $table_postmeta pm4 ON
                        pm4.post_id = pm1.id AND
                        pm4.meta_key = 'repetition-start' AND
                        pm4.meta_value BETWEEN 0 AND " . strtotime($date . 'T23:59') . " 
                    INNER JOIN $table_postmeta pm5 ON
                        pm5.post_id = pm1.id AND (
                            (
                                pm5.meta_key = 'repetition-end' AND
                                pm5.meta_value BETWEEN " . strtotime($date) . " AND 3000000000
                            ) OR
                            (
                                pm1.id not in (SELECT post_id FROM $table_postmeta WHERE meta_key = 'repetition-end')
                            )
                        )                        
                ";
                }

                // Filter only from a specific start date.
                // Rep-End must be > Min Date (0:00)
                if ($minTimestamp) {
                    $dateQuery = "
                    INNER JOIN $table_postmeta pm4 ON
                        pm4.post_id = pm1.id AND (
                            ( 
                                pm4.meta_key = 'repetition-end' AND
                                pm4.meta_value > " . $minTimestamp . "
                            ) OR
                            (
                                pm1.id not in (SELECT post_id FROM $table_postmeta WHERE meta_key = 'repetition-end')
                            )
                        )
                ";
                }

                // Complete query
                $query = "
                    SELECT DISTINCT pm1.* from $table_posts pm1
                    " . $dateQuery . "
                    WHERE
                        pm1.id in (" . implode(",", $postIds) . ") AND
                        pm1.post_type = '" . \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType() . "' AND
                        pm1.post_status IN ('" . implode("','", $postStatus) . "')
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

                // Filter by configured days
                if($date) {
                    $posts = array_filter($posts, function ($post) use ($date) {
                        if($weekdays = get_post_meta($post->ID, 'weekdays', true)) {
                            $weekdays = $weekdays;
                            $day = date('N', strtotime($date));
                            return in_array($day, $weekdays);
                        }
                        return true;
                    });
                }
            }

            // if returnAsModel == TRUE the result is a timeframe model instead of a wordpress object
            if ($returnAsModel) {
                foreach ($posts as &$post) {
                    $post = new \CommonsBooking\Model\Timeframe($post);
                }
            }

            Plugin::setCacheItem($posts, $customId);
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

        $customId = md5(serialize($types));
        if (Plugin::getCacheItem($customId)) {
            return Plugin::getCacheItem($customId);
        } else {
            global $wpdb;
            $table_postmeta = $wpdb->prefix . 'postmeta';

            $itemQuery = "";

            $items = array_filter($items);
            $locations = array_filter($locations);

            // Query for item(s)
            if (count($items) > 0) {
                $itemQuery = " 
                    INNER JOIN $table_postmeta pm2 ON
                        pm2.post_id = pm1.post_id AND
                        pm2.meta_key = 'item-id' AND
                        pm2.meta_value IN (" . implode(',', $items) . ")
                ";
            }

            // Query for location(s)
            $locationQuery = "";
            if (count($locations) > 0) {
                $locationQuery = " 
                    INNER JOIN $table_postmeta pm3 ON
                        pm3.post_id = pm1.post_id AND
                        pm3.meta_key = 'location-id' AND
                        pm3.meta_value IN (" . implode(',', $locations) . ")
                ";
            }

            // Complete query, including types
            $query = "
                SELECT DISTINCT pm1.post_id from $table_postmeta pm1 
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

            Plugin::setCacheItem($posts, $customId);

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
