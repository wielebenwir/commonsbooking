<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

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
     * Get all Locations current user is allowed to see/edit
     * @return array
     */
    public static function getByCurrentUser()
    {
        $current_user = wp_get_current_user();
        $items = [];

        if (Plugin::getCacheItem(static::getPostType())) {
            return Plugin::getCacheItem(static::getPostType());
        } else {
            // Get all Locations where current user is author
            $args = array(
                'post_type' => static::getPostType(),
                'author'    => $current_user->ID
            );
            $query = new \WP_Query($args);
            if ($query->have_posts()) {
                $items = array_merge($items, $query->get_posts());
            }

            // get all items where current user is assigned as admin
            $args = array(
                'post_type'  => static::getPostType(),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_' . static::getPostType() . '_admins',
                        'value'   => '"' . $current_user->ID . '"',
                        'compare' => 'like'
                    )
                )
            );

            // workaround: if user has admin-role get all available items
            // TODO: better solution to check if user has administrator role
            if (in_array('administrator', $current_user->roles)) {
                unset($args);
                $args = array(
                    'post_type' => static::getPostType(),
                );
            }


            $query = new \WP_Query($args);
            if ($query->have_posts()) {
                $items = array_merge($items, $query->get_posts());
            }

            Plugin::setCacheItem($items, static::getPostType());

            return $items;
        }
    }

    /**
     * Returns cb-posts for a user (respects author and assigned admins).
     *
     * @param $userId
     * @param false $asModel
     *
     * @return array
     */
    public static function getByUserId($userId, $asModel = false)
    {
        $cbPosts = [];

        if (Plugin::getCacheItem()) {
            return Plugin::getCacheItem();
        } else {
            // Get all Locations where current user is author
            $args = array(
                'post_type' => static::getPostType(),
                'author'    => $userId
            );
            $query = new \WP_Query($args);
            if ($query->have_posts()) {
                $cbPosts = array_merge($cbPosts, $query->get_posts());
            }

            // get all cbPosts where current user is assigned as admin
            $args = array(
                'post_type'  => static::getPostType(),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_' . static::getPostType() . '_admins',
                        'value'   => '"' . $userId . '"',
                        'compare' => 'like'
                    )
                )
            );

            $query = new \WP_Query($args);
            if ($query->have_posts()) {
                $cbPosts = array_merge($cbPosts, $query->get_posts());
            }

            if ($asModel) {
                foreach ($cbPosts as &$cbPost) {
                    $class = static::getModelClass();
                    $cbPost = new $class($cbPost);
                }
            }

            Plugin::setCacheItem($cbPosts);

            return $cbPosts;
        }
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
    public static function get($args = array(), $bookable = false)
    {
        $posts = [];
        $args['post_type'] = static::getPostType();

        if (Plugin::getCacheItem()) {
            return Plugin::getCacheItem();
        } else {
            $defaults = array(
                'post_status' => array('publish', 'inherit'),
            );

            $queryArgs = wp_parse_args($args, $defaults);
            $query = new \WP_Query($queryArgs);

            if ($query->have_posts()) {
                $posts = $query->get_posts();
                foreach ($posts as $key => &$post) {
                    $class = static::getModelClass();
                    $post = new $class($post);

                    // If items shall be bookable, we need to check...
                    if ($bookable && ! $post->isBookable()) {
                        unset($posts[$key]);
                    }
                }
            }

            Plugin::setCacheItem($posts);

            return $posts;
        }
    }

}
