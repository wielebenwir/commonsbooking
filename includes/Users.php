<?php

use CommonsBooking\Plugin;


/**
 * Checks if current user is allowed to edit custom post.
 *
 * @param $post
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToEdit($post)
{
    $current_user = wp_get_current_user();
    $isAuthor     = intval($current_user->ID) == intval($post->post_author);
    $isAdmin      = false;
    if (in_array('administrator', (array)$current_user->roles)) {
        $isAdmin = true;
    }

    // Check if it is the main query and one of our custom post types
    if ( ! $isAdmin && ! $isAuthor) {
        $admins = [];

        // Get allowed admins for timeframe listing
        if ($post->post_type == Timeframe::$postType) {
            // Get assigned location
            $locationId       = get_post_meta($post->ID, 'location-id', true);
            $locationAdminIds = get_post_meta($locationId, '_'.Location::$postType.'_admins', true);
            if (is_string($locationAdminIds)) {
                if (strlen($locationAdminIds) > 0) {
                    $locationAdminIds = [$locationAdminIds];
                } else {
                    $locationAdminIds = [];
                }
            }
            $locationAdminIds[] = get_post_field('post_author', $locationId);

            // Get assigned item
            $itemId       = get_post_meta($post->ID, 'item-id', true);
            $itemAdminIds = get_post_meta($itemId, '_'.Item::$postType.'_admins', true);
            if (is_string($itemAdminIds)) {
                if (strlen($itemAdminIds) > 0) {
                    $itemAdminIds = [$itemAdminIds];
                } else {
                    $itemAdminIds = [];
                }
            }
            $itemAdminIds[] = get_post_field('post_author', $itemId);

            if (
                is_array($locationAdminIds) && count($locationAdminIds) &&
                is_array($itemAdminIds) && count($itemAdminIds)
            ) {
                $admins = array_merge($locationAdminIds, $itemAdminIds);
            }
        }

        // Get allowed admins for Location / Item Listing
        if (in_array(
            $post->post_type,
            [
                Location::$postType,
                Item::$postType,
            ]
        )
        ) {
            // post-related admins (returns string if single result and array if multiple results)
            $admins = get_post_meta($post->ID, '_'.$post->post_type.'_admins', true);
        }

        if (
            (is_string($admins) && $current_user->ID != $admins) ||
            is_array($admins) && ! in_array($current_user->ID, $admins)
        ) {
            return false;
        }
    }

    return true;
}

/**
 * Validates if current user is allowed to edit current post in admin.
 *
 * @param $current_screen
 */
function commonsbooking_validate_user_on_edit($current_screen)
{
    if ($current_screen->base == "post" && in_array($current_screen->id, Plugin::getCustomPostTypesLabels())) {
        if (array_key_exists('action', $_GET) && $_GET['action'] == 'edit') {
            $post = get_post($_GET['post']);
            if ( ! commonsbooking_isCurrentUserAllowedToEdit($post)) {
                die('Access denied');
            };
        }
    }
}

add_action('current_screen', 'commonsbooking_validate_user_on_edit', 10, 1);

/**
 * Applies listing restriction for item and location admins.
 */
add_filter(
    'the_posts',
    function ($posts, $query) {
        if (is_admin() && array_key_exists('post_type', $query->query)) {
            // Post type of current list
            $postType = $query->query['post_type'];

            $current_user = wp_get_current_user();
            $isAdmin      = false;
            if (in_array('administrator', (array)$current_user->roles)) {
                $isAdmin = true;
            }

            // Check if it is the main query and one of our custom post types
            if ( ! $isAdmin && $query->is_main_query() && in_array($postType, Plugin::getCustomPostTypesLabels())) {
                foreach ($posts as $key => $post) {
                    if ( ! commonsbooking_isCurrentUserAllowedToEdit($post)) {
                        unset($posts[$key]);
                    }
                }
            }
        }

        return $posts;
    },
    10,
    2
);

// TODO: Check if still necessary. User check is implemented in CustomPostType/Timframe -> getTemplate()
// Redirect to startpage if user is not allowed to edit timeframe
function commonsbooking_timeframe_redirect()
{
    global $post;
    if (
        $post &&
        $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType &&
        (
            ( ! current_user_can('administrator') && get_current_user_id() != $post->post_author) ||
            ! is_user_logged_in()
        )
    ) {
        wp_redirect(home_url('/'));
        exit;
    }
}
