<?php


namespace CommonsBooking\Repository;


abstract class BookablePost extends PostRepository
{
    abstract protected static function getPostType();
    abstract protected static function getModelClass();

    /**
     * Returns an array of CB item post objects
     *
     *
     * @param array $args WP Post args
     * @param bool $bookable
     *
     * @return array
     */
    public static function get($args = array(), $bookable = false) {

        $args['post_type'] =  static::getPostType();

        $defaults = array(
            'post_status' => array('publish', 'inherit'),
        );

        $queryArgs = wp_parse_args($args, $defaults);
        $query = new \WP_Query($queryArgs);

        if ($query->have_posts()) {
            $posts = $query->get_posts();
            foreach($posts as $key => &$post) {
                $class = static::getModelClass();
                $post = new $class($post);

                // If items shall be bookable, we need to check...
                if($bookable && !$post->isBookable()) {
                    unset($posts[$key]);
                }
            }

        }
        return $posts;
    }

}
