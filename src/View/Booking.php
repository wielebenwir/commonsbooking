<?php


namespace CommonsBooking\View;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Booking extends View
{

    public static $timeformat = 'd.m.Y H:i:s';

    protected static function getDefaultParams(\WP_Post $post) {
        $itemId = get_post_meta($post->ID, 'item-id', true);
        $locationId = get_post_meta($post->ID, 'location-id', true);

        $item = get_post($itemId);
        $location = get_post($locationId);

        $startDate = get_post_meta($post->ID, 'start-date', true);
        $endDate = get_post_meta($post->ID, 'end-date', true);

        return array(
            'post' => $post,
            'wp_nonce' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'start_date_string' => date(self::$timeformat, $startDate),
            'end_date_string' => date(self::$timeformat, $endDate),
            'location' => array(
                'post' => $location,
                'thumbnail' => get_the_post_thumbnail( $location->ID, 'thumbnail' )
            ),
            'item' =>  array(
                'post' => $item,
                'thumbnail' => get_the_post_thumbnail( $item->ID, 'thumbnail' )
            ),
            'user' => wp_get_current_user(),
            'type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID, // statically for bookings, when it works we make it dynamic
            'startDate' => $startDate,
            'endDate' => $endDate
        );
    }

    public static function cancelled(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }
        echo self::render(
            'timeframe/booking/cancelled.html.twig',
            self::getDefaultParams($post)
        );
    }

    public static function confirmed(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }

        $current_user = wp_get_current_user();
        if(intval($current_user->ID) == intval($post->post_author)) {
            echo self::render(
                'timeframe/booking/unconfirmed.html.twig',
                array_merge(
                    self::getDefaultParams($post),
                    array(
                        'type' => Timeframe::BOOKING_CANCELED_ID,
                        'postStatus' => 'cancelled',
                        'submitLabel' => __( 'Stornieren', CB_TEXTDOMAIN )
                    )
                )
            );
        } else {
            self::notAllowed();
        }
    }

    public static function unconfirmed(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }

        echo self::render(
            'timeframe/booking/unconfirmed.html.twig',
            array_merge(
                self::getDefaultParams($post),
                array(
                    'postStatus' => 'confirmed',
                    'submitLabel' => __( 'Buchen', CB_TEXTDOMAIN )
                )
            )
        );
    }

    public static function published(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }
        echo self::render(
            'timeframe/booking/published.html.twig',
            self::getDefaultParams($post)
        );
    }

    public static function notAllowed()
    {
        echo self::render(
            'timeframe/booking/not-allowed.html.twig',
            []
        );
    }
}
