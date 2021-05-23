<?php

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;


/**
 * Checks if current user is allowed to edit custom post.
 *
 * @param $post
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToEdit($post): bool
{
    if(!$post) return false;
    $current_user = wp_get_current_user();
    $isAuthor     = intval($current_user->ID) == intval($post->post_author);
    $isAdmin      = commonsbooking_isCurrentUserAdmin();
    $isAllowed    = $isAdmin || $isAuthor;

    // Check if it is the main query and one of our custom post types
    if ( ! $isAllowed ) {
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
        } elseif (in_array(
            $post->post_type,
            [
                Location::$postType,
                Item::$postType,
            ]
        )) {
            // Get allowed admins for Location / Item Listing
            // post-related admins (returns string if single result and array if multiple results)
            $admins = get_post_meta($post->ID, '_'.$post->post_type.'_admins', true);
        }

        $isAllowed = (is_string($admins) && $current_user->ID === $admins) ||
            (is_array($admins) && in_array($current_user->ID.'', $admins, true));
    }

    return $isAllowed;
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
 * modifies admin bar due to user restrictions (e.g. remove edit link from admin bar if user not allowed to edit)
 *
 * @return void
 */
function commonsbooking_modify_admin_bar() {
    global $wp_admin_bar;
    global $post;
    if (! commonsbooking_isCurrentUserAllowedToEdit($post) ) {
        $wp_admin_bar->remove_menu('edit');
    }
    
}

add_action( 'wp_before_admin_bar_render', 'commonsbooking_modify_admin_bar' );

/**
 * Applies listing restriction for item and location admins.
 */
add_filter(
    'the_posts',
    function ($posts, $query) {
        if (is_admin() && array_key_exists('post_type', $query->query)) {
            // Post type of current list
            $postType = $query->query['post_type'];
            $isAdmin = commonsbooking_isCurrentUserAdmin();

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

// Check if current user has admin role
function commonsbooking_isCurrentUserAdmin()
{
    $user = wp_get_current_user();
    return apply_filters('commonsbooking_isCurrentUserAdmin', in_array('administrator', $user->roles), $user);
}

// Check if current user has subscriber role
function commonsbooking_isCurrentUserSubscriber()
{
    $user = wp_get_current_user();
    return apply_filters('commonsbooking_isCurrentUserSubscriber', in_array('subscriber', $user->roles), $user);
}

/**
 * Returns true if user is allowed to book based on the timeframe configuration (user role)
 *
 * @param mixed $timeframeID
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToBook($timeframeID)
{
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $allowedUserRoles = get_post_meta($timeframeID, 'allowed_user_roles', true);

    if ( empty($allowedUserRoles) ) {
        return true;
    }

    $match = array_intersect($user_roles, $allowedUserRoles);

    return count($match) > 0;
}
