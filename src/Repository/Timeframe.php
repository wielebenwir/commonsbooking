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
        $posts = [];
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
            // Default query
            $args = array(
                'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType(),
                'post_status' => $postStatus
            );

            if ($date) {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    [self::getTimeRangeQuery($date)],
                    [
                        'key'     => 'type',
                        'value'   => $types,
                        'compare' => 'IN'
                    ]
                );
            } else {
                $args['meta_query'] = array(
                    [
                        'key'     => 'type',
                        'value'   => $types,
                        'compare' => 'IN'
                    ]
                );
            }

            // Filter only from a specific start date.
            if($minTimestamp) {
                $args['meta_query'] = array_merge(
                    [
                        'relation' => 'AND',
                        [
                            'key'     => 'repetition-end',
                            'value'   => $minTimestamp,
                            'compare' => '>',
                            'type'    => 'numeric'
                        ]
                    ],
                    $args['meta_query']
                );
            }

            $query = new \WP_Query($args);
            if ($query->have_posts()) {
                $posts = $query->get_posts();

                // If there are locations or items to be filtered, we iterate through
                // query result because wp_query is to slow for meta-querying them.
                if (count($locations) || count($items)) {
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
     * @param string|null $date Date-String
     *
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
