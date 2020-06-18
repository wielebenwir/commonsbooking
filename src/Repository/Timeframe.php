<?php


namespace CommonsBooking\Repository;


class Timeframe
{

    protected static function getTimeRangeQuery($date)
    {
        return array(
            'relation' => "OR",
            // Timeframe has any overlap with current day
            array(
                'relation' => "AND",
                array(
                    'key' => 'start-date',
                    'value' => [
                        0,
                        strtotime($date . 'T23:59')
                    ],
                    'compare' => 'BETWEEN',
                    'type' => 'numeric'
                ),
                array(
                    'key' => 'end-date',
                    'value' => [
                        strtotime($date),
                        3000000000
                    ],
                    'compare' => 'BETWEEN',
                    'type' => 'numeric'
                )
            ),
            // start date is before end of current day and there is no rep end
            array(
                'relation' => "AND",
                array(
                    'key' => 'start-date',
                    'value' => strtotime($date . 'T23:59'),
                    'compare' => '<=',
                    'type' => 'numeric'
                ),
                array(
                    'key' => 'end-date',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
    }

    public static function get($locations = [], $items = [], $types = [], $date) {
        $posts = [];
        // Default query
        $args = array(
            'post_type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType(),
            'meta_query' => self::getTimeRangeQuery($date),
            'post_status' => array('confirmed', 'unconfirmed', 'publish', 'inherit')
        );

        // Filter by type first
        if (count($types)) {
            $types = [
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
                \CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
            ];
            $args['meta_query'] = array(
                'relation' => 'AND',
                [self::getTimeRangeQuery($date)],
                [
                    'key' => 'type',
                    'value' => $types,
                    'compare' => 'IN'
                ]
            );
        }
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $posts = $query->get_posts();

            // If there are locations or items to be filtered, we iterate through
            // query result because wp_query is to slow for meta-querying them.
            if(count($locations) || count($items)) {
                $posts = array_filter($posts, function ($post) use ($locations, $items) {
                    $location = get_post_meta($post->ID, 'location-id', true);
                    $item = get_post_meta($post->ID, 'item-id', true);

                    return (!$location && !$item) ||
                        (!$location && in_array($item, $items)) ||
                        (in_array($location, $locations) && !$item) ||
                        (in_array($location, $locations) && in_array($item, $items));
                });
            }
        }

        return $posts;
    }

}
