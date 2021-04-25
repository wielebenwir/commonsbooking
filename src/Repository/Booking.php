<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Booking extends PostRepository
{
    /**
     * @param $startDate
     * @param $endDate
     * @param $location
     * @param $item
     *
     * @return null|\CommonsBooking\Model\Booking
     * @throws \Exception
     */
    public static function getBookingByDate($startDate, $endDate, $location, $item): ?\CommonsBooking\Model\Booking
    {
        // Default query
        $args = array(
            'post_type'   => Timeframe::getPostType(),
            'meta_query'  => array(
                'relation' => "AND",
                array(
                    'key'     => 'repetition-start',
                    'value'   => intval($startDate),
                    'compare' => '=',
                    'type'    => 'numeric',
                ),
                array(
                    'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
                    'value'   => $endDate,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'type',
                    'value'   => Timeframe::BOOKING_ID,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'location-id',
                    'value'   => $location,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'item-id',
                    'value'   => $item,
                    'compare' => '=',
                ),
            ),
            'post_status' => array('confirmed','unconfirmed'),
            'nopaging'    => true,
        );

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $posts = $query->get_posts();
            $posts = array_filter($posts, function ($post) {
                return in_array($post->post_status, array('confirmed','unconfirmed'));
            });

            // If there is exactly one result, return it.
            if (count($posts) == 1) {
                $booking = new \CommonsBooking\Model\Booking($posts[0]);
                if(in_array($booking->getPost()->post_status, array('confirmed','unconfirmed'))) {
                    return $booking;
                }
            }

            // This shouldn't happen.
            if (count($posts) > 1) {
                throw new \Exception(__CLASS__."::".__LINE__.": Found more than one bookings");
            }
        }

        return null;
    }

    /**
     * Returns all bookings, allowed to see/edit for current user.
     *
     * @param bool $asModel
     * @param null $startDate
     * @return array
     * @throws \Exception
     */
    public static function getForCurrentUser($asModel = false, $startDate = null)
    {
        if (!is_user_logged_in()) return [];

        $current_user = wp_get_current_user();
        $customId = $current_user->ID;

        if (Plugin::getCacheItem($customId)) {
            return Plugin::getCacheItem($customId);
        } else {
            $posts = \CommonsBooking\Repository\Timeframe::get(
                [],
                [],
                [Timeframe::BOOKING_ID],
                null,
                $asModel,
                $startDate,
                ['canceled', 'confirmed', 'unconfirmed']
            );
            if ($posts) {
                // Check if it is the main query and one of our custom post types
                $posts = array_filter($posts, function ($post) {
                    return commonsbooking_isCurrentUserAllowedToEdit($post);
                });
            }
            Plugin::setCacheItem($posts, $customId);
        }

        return $posts;
    }

}
