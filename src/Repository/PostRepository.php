<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

abstract class PostRepository
{

    public static function getByPostById($postId)
    {
        $post = get_post($postId);

        if($post instanceof \WP_Post) {
            if($post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType()) {
                $type = get_post_meta($post->ID, 'type', true);
                switch ($type) {
                    case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID: //booking
                        return new \CommonsBooking\Model\Booking($post);
                        break;
                    case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_CANCELED_ID: //booking cancelled   
                        return new \CommonsBooking\Model\Booking($post);
                        break;
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

     /**
     * Returns CB posttypes by label
     * @param $postTypeLabel
     * @return string
     * @throws \Exception
     */
    public static function labelToPosttype($postTypeLabel) {

        // 'cb_items', 'cb_locations'… 
       switch ($postTypeLabel) {
            case 'cb_items':
                $postType = Item::getPostType();
                break;
            case 'cb_locations':
                $postType = Location::getPostType();
                break;
            case 'cb_timeframes':
            case 'cb_bookings':
                $postType = Timeframe::getPostType();
                break;
            default:
                throw new \Exception(__CLASS__ . "::" . __FUNCTION__ . ": Invalid or empty post type: " . ($args['post_type']));
        };
        return $postType;
    }
     /**
     * Filters to Query @TODO
     * @param $postTypeLabel
     * @return string
     * @throws \Exception
     */
    public static function filtersToQuery($filters=array()) {

        return $query;
    }
}
