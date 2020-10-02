<?php


namespace CommonsBooking\Repository;


abstract class PostRepository
{

    /**
     * Returns post by id as CB-CPT if possible.
     * @param $postId
     *
     * @return \CommonsBooking\Model\Booking|\CommonsBooking\Model\Item|\CommonsBooking\Model\Location|mixed|\WP_Post
     * @throws \Exception
     */
    public static function getPostById($postId)
    {
        $post = get_post($postId);

        if($post instanceof \WP_Post) {
            if($post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType()) {
                $type = get_post_meta($post->ID, 'type', true);
                switch ($type) {
                    case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID: //booking
                        return new \CommonsBooking\Model\Booking($post);
                    case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_CANCELED_ID: //booking cancelled   
                        return new \CommonsBooking\Model\Booking($post);
                }
            }

            if($post->post_type == \CommonsBooking\Wordpress\CustomPostType\Item::getPostType()) {
                return new \CommonsBooking\Model\Item($post);
            }

            if($post->post_type == \CommonsBooking\Wordpress\CustomPostType\Location::getPostType()) {
                return new \CommonsBooking\Model\Location($post);
            }
        }
        return $post;
    }

}
