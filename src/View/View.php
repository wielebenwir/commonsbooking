<?php


namespace CommonsBooking\View;

abstract class View
{

    /**
     * List of allowed query params for shortcodes.
     * @var string[]
     */
    protected static $allowedShortCodeArgs= array(
        'p'             => '', // post id
        // Author: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
        'author'        => '',
        'author_name'   => '',
        // Category: https://developer.wordpress.org/reference/classes/wp_query/#category-parameters
        'cat'           => '',
        'cat_name'      => '',
        // Tag: https://developer.wordpress.org/reference/classes/wp_query/#tag-parameters
        'tag'           => '',
        'tag_id'        => '',
        // Status https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
        'post_status'   => '',
        // Pagination: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
        'posts_per_page'=> '',
        'nopaging'      => '',
        'offset'        => ''
    );

}
