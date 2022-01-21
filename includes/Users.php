<?php

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;


/**
 * Checks if current user is allowed to edit custom post.
 *
 * @param $post
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToEdit( $post ): bool {
	if ( ! $post ) {
		return false;
	}
	$current_user = wp_get_current_user();
	$isAuthor     = intval( $current_user->ID ) == intval( $post->post_author );
	$isAdmin      = commonsbooking_isCurrentUserAdmin();
	$isAllowed    = $isAdmin || $isAuthor;

	// Check if it is the main query and one of our custom post types
	if ( ! $isAllowed ) {
		$admins = [];

		// Get allowed admins for timeframe or booking listing
		if (
			in_array($post->post_type, [
				Timeframe::$postType,
				Booking::$postType,
				Restriction::$postType
			])
		) {
			$postModel = \CommonsBooking\Wordpress\CustomPostType\CustomPostType::getModel($post);

			// Get assigned location
			$locationId       = get_post_meta( $post->ID, $postModel::META_LOCATION_ID, true );
			$locationAdminIds = get_post_meta( $locationId, '_' . Location::$postType . '_admins', true );
			if ( is_string( $locationAdminIds ) ) {
				if ( strlen( $locationAdminIds ) > 0 ) {
					$locationAdminIds = [ $locationAdminIds ];
				} else {
					$locationAdminIds = [];
				}
			}
			$locationAdminIds[] = get_post_field( 'post_author', $locationId );

			// Get assigned item
			$itemId       = get_post_meta( $post->ID, $postModel::META_ITEM_ID, true );
			$itemAdminIds = get_post_meta( $itemId, '_' . Item::$postType . '_admins', true );
			if ( is_string( $itemAdminIds ) ) {
				if ( strlen( $itemAdminIds ) > 0 ) {
					$itemAdminIds = [ $itemAdminIds ];
				} else {
					$itemAdminIds = [];
				}
			}
			$itemAdminIds[] = get_post_field( 'post_author', $itemId );

			if (
				is_array( $locationAdminIds ) && count( $locationAdminIds ) &&
				is_array( $itemAdminIds ) && count( $itemAdminIds )
			) {
				$admins = array_merge( $locationAdminIds, $itemAdminIds );
			}
		} elseif ( in_array(
			$post->post_type,
			[
				Location::$postType,
				Item::$postType,
			]
		) ) {
			// Get allowed admins for Location / Item Listing
			// post-related admins (returns string if single result and array if multiple results)
			$admins = get_post_meta( $post->ID, '_' . $post->post_type . '_admins', true );
		}

		$isAllowed = ( is_string( $admins ) && $current_user->ID === $admins ) ||
		             ( is_array( $admins ) && in_array( $current_user->ID . '', $admins, true ) );
	}

	return $isAllowed;
}

/**
 * Validates if current user is allowed to edit current post in admin.
 *
 * @param $current_screen
 */
function commonsbooking_validate_user_on_edit( $current_screen ) {
	if ( $current_screen->base == "post" && in_array( $current_screen->id, Plugin::getCustomPostTypesLabels() ) ) {
		if ( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'edit' ) {
			$post = get_post( $_GET['post'] );
			if ( ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
				die( 'Access denied' );
			}
		}
	}
}

add_action( 'current_screen', 'commonsbooking_validate_user_on_edit', 10, 1 );


/**
 * modifies admin bar due to user restrictions (e.g. remove edit link from admin bar if user not allowed to edit)
 *
 * @return void
 */
function commonsbooking_modify_admin_bar() {
	global $wp_admin_bar;
	global $post;
	if ( ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
		$wp_admin_bar->remove_menu( 'edit' );
	}

}

add_action( 'wp_before_admin_bar_render', 'commonsbooking_modify_admin_bar' );

/**
 * Applies listing restriction for item and location admins.
 */
add_filter(
	'the_posts',
	function ( $posts, $query ) {
		if ( is_admin() && array_key_exists( 'post_type', $query->query ) ) {
			// Post type of current list
			$postType = $query->query['post_type'];
			$isAdmin  = commonsbooking_isCurrentUserAdmin();

			// Check if it is the main query and one of our custom post types
			if ( ! $isAdmin && $query->is_main_query() && in_array( $postType, Plugin::getCustomPostTypesLabels() ) ) {
				foreach ( $posts as $key => $post ) {
					if ( ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
						unset( $posts[ $key ] );
					}
				}
			}

			// Save posts to global variable for later use -> fix of counts in admin lists
			if(
				array_key_exists('post_type', $_GET) &&
				is_array($query->query) && array_key_exists('post_type', $query->query)
			) {
				global ${'posts' . $query->query['post_type']};
				${'posts' . $query->query['post_type']} = $posts;
			}
		}

		return $posts;
	},
	10,
	2
);

// Add filter to change post counts in admin lists for custom post types.
foreach ( Plugin::getCustomPostTypes() as $custom_post_type ) {
	add_filter( 'views_edit-' . $custom_post_type::getPostType(), 'commonsbooking_custom_view_count', 10, 1 );
}

// Filter function for fix of counts in admin lists for custom post types.
function commonsbooking_custom_view_count( $views ) {
	global $current_screen;
	return commonsbooking_fix_view_counts( str_replace( 'edit-', '', $current_screen->id ), $views );
}

// fixes counts for custom posts countings in admin list
function commonsbooking_fix_view_counts( $postType, $views ) {
	// admin is allowed to see all posts
	if(current_user_can('administrator')) {
		return $views;
	}

	global ${'posts' . $postType};
	$timeFramePosts = ${'posts' . $postType};

	$counts = [
		'all' => count( $timeFramePosts )
	];

	// add counts for differentp states
	foreach ( $timeFramePosts as $post ) {
		if ( ! array_key_exists( $post->post_status, $counts ) ) {
			$counts[ $post->post_status ] = 0;
		}

		$counts[ $post->post_status ] ++;
	}

	// replace output
	foreach ( $counts as $type => $value ) {
		$views[ $type ] = preg_replace( '/\(.+\)/U', '(' . $value . ')', $views[ $type ] );
	}

	// return only views, which are contained in $counts array.
	return array_intersect_key( $views, $counts );
}

// Check if current user has admin role
function commonsbooking_isCurrentUserAdmin() {
	$user = wp_get_current_user();

	return apply_filters( 'commonsbooking_isCurrentUserAdmin', in_array( 'administrator', $user->roles ), $user );
}

// Check if current user has subscriber role
function commonsbooking_isCurrentUserSubscriber() {
	$user = wp_get_current_user();

	return apply_filters( 'commonsbooking_isCurrentUserSubscriber', in_array( 'subscriber', $user->roles ), $user );
}

// check if current user has CBManager role
function commonsbooking_isCurrentUserCBManager() {
	$user = wp_get_current_user();

	return apply_filters( 'commonsbooking_isCurrentUserCBManager', in_array( Plugin::$CB_MANAGER_ID, $user->roles ), $user );

}

/**
 * Returns true if user is allowed to book based on the timeframe configuration (user role)
 *
 * @param mixed $timeframeID
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToBook( $timeframeID ) {
	$current_user     = wp_get_current_user();
	$user_roles       = $current_user->roles;
	$allowedUserRoles = get_post_meta( $timeframeID, 'allowed_user_roles', true );

	if ( empty( $allowedUserRoles ) ) {
		return true;
	}

	$match = array_intersect( $user_roles, $allowedUserRoles );

	return count( $match ) > 0;
}
