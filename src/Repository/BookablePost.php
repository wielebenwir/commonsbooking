<?php


namespace CommonsBooking\Repository;


abstract class BookablePost extends PostRepository
{
    /**
     * @return string
     */
    abstract protected static function getPostType();

    /**
     * @return mixed
     */
    abstract protected static function getModelClass();

    /**
     * Returns cb-posts for a user (respects author and assigned admins).
     * @param $userId
     * @param false $asModel
     *
     * @return array
     */
    public static function getByUserId($userId, $asModel = false) {
        $cbPosts = [];

        // Get all Locations where current user is author
        $args = array(
            'post_type' => static::getPostType(),
            'author' => $userId
        );
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $cbPosts = array_merge($cbPosts, $query->get_posts());
        }

        // get all cbPosts where current user is assigned as admin
        $args = array(
            'post_type' => static::getPostType(),
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'   => '_' . static::getPostType() . '_admins',
                    'value' => '"' . $userId . '"',
                    'compare' => 'like'
                )
            )
        );

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $cbPosts = array_merge($cbPosts, $query->get_posts());
        }

        if($asModel) {
            foreach($cbPosts as &$cbPost) {
                $class = static::getModelClass();
                $cbPost = new $class($cbPost);
            }
        }

        return $cbPosts;
    }

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
        $posts = [];
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
