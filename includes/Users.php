<?php

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;


/**
 * Checks if current user is allowed to edit custom post.
 * @param $post
 *
 * @return bool
 * @throws Exception
 */
function commonsbooking_isCurrentUserAllowedToEdit( $post ): bool {
	$current_user = wp_get_current_user();

	return commonsbooking_isUserAllowedToEdit($post,$current_user);
}

/**
 * Checks if user is allowed to edit custom post.
 *
 * TODO: Can be integrated into isCurrentUserAllowedToEdit and severely shortened after PR#1141
 *
 * @param $post
 * @param $user
 *
 * @return bool
 * @throws Exception
 */
function commonsbooking_isUserAllowedToEdit( $post, $user): bool {
	if (! Plugin::isPostCustomPostType($post) ) {
		return false;
	}

	if (! is_user_logged_in()){ return false; }

	$isAuthor     = intval( $user->ID ) == intval( $post->post_author );
	$isAdmin      = commonsbooking_isUserAdmin($user);
	$isPostAdmin    = $isAdmin || $isAuthor;

	// Check if it is the main query and one of our custom post types
	if ( ! $isPostAdmin ) {

		if(!($post instanceof WP_Post)) {
			$post = $post->getPost();
		}
		$postModel = \CommonsBooking\Wordpress\CustomPostType\CustomPostType::getModel($post);

		$admins = $postModel->getAdmins();

		$isPostAdmin = ( is_string( $admins ) && $user->ID === $admins ) ||
		             ( is_array( $admins ) && in_array( $user->ID . '', $admins, true ) );
	}
    $isPostEditor = current_user_can('edit_post', $post->ID);

    return $isPostAdmin && $isPostEditor;
}

/**
 * Validates if current user is allowed to edit current post in admin.
 *
 * @param $current_screen
 */
function commonsbooking_validate_user_on_edit( $current_screen ) {
	if ( $current_screen->base == "post" && in_array( $current_screen->id, Plugin::getCustomPostTypesLabels() ) ) {
		if ( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'edit' ) {
			$post = get_post( intval($_GET['post']) );
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
	//check for CPT before evaluation of permission, use short-circuit to prevent invalid data access
	if ( Plugin::isPostCustomPostType($post) && ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
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

/**
 * Will check if user has one of the admin roles and is therefore considered an admin for CB.
 * Admin roles can be extended with the filter commonsbooking_admin_roles.
 *
 * @param   \WP_User  $user
 *
 * @return bool
 */
function commonsbooking_isUserAdmin(WP_User $user) {
	$adminRoles = ['administrator'];
	$adminRoles = apply_filters('commonsbooking_admin_roles', $adminRoles);
	foreach ($adminRoles as $adminRole) {
		if (in_array($adminRole, $user->roles)) {
			return true;
		}
	}
	return false;
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
function commonsbooking_isCurrentUserAllowedToBook( $timeframeID ):bool {
	$allowedUserRoles = get_post_meta( $timeframeID, 'allowed_user_roles', true );

	if ( empty( $allowedUserRoles ) || ( current_user_can('administrator') ) ) {
		return true;
	}

	$current_user     = wp_get_current_user();
	$user_roles       = $current_user->roles;

	$match = array_intersect( $user_roles, $allowedUserRoles );

	return count( $match ) > 0;
}

/**
 * Determines weather a user may read the current post.
 * It only makes sense to check this with booking posts as all CPTs are / should be public
 * TODO After PR #1141 refactor doubled code
 *
 * @param $booking - A boooking of the cb_booking type
 *
 * @return void
 */
function commonsbooking_isCurrentUserAllowedToSee( $booking ):bool{
    if ( ! $booking ) {
        return false;
    }

    $user = wp_get_current_user();

    if ($user){
        return commonsbooking_isUserAllowedToSee( $booking, $user );
    }
    else {
        return false;
    }

}

/**
 * Determines weather a user may read the current post.
 * It only makes sense to check this with booking posts as all CPTs are / should be public
 * TODO Refactor after PR #1141
 * @param $booking
 * @param WP_User $user
 *
 * @return bool
 */
function commonsbooking_isUserAllowedToSee($booking, WP_User $user): bool
{
    if ($booking instanceof \CommonsBooking\Model\Booking){
        $bookingModel = $booking;
    }
    elseif ($booking instanceof WP_Post){
        $bookingModel = \CommonsBooking\Wordpress\CustomPostType\CustomPostType::getModel( $booking );
    }
    else {
        return false;
    }

    $isAuthor  = $user->ID === intval( $booking->post_author );
    $isAdmin   = commonsbooking_isUserAdmin( $user );
    $isAllowed = $isAdmin || $isAuthor;

    if ( ! $isAllowed) {
        $admins    = $bookingModel->getAdmins();
        $isAllowed = (is_string( $admins ) && $user->ID == $admins) ||
                     (is_array( $admins ) && in_array( $user->ID . '', $admins, true ));
    }

    return $isAllowed;
}

/**
 * Checks if the given user_id and user_hash match, generates
 * a new hash from the given user id and checks it against the given hash.
 *
 * Used by Service\iCalendar for authentication.
 *
 * @param $user_id
 * @param $user_hash
 *
 * @return bool
 */
function commonsbooking_isUIDHashComboCorrect( $user_id, $user_hash): bool {
	if (wp_hash($user_id) == $user_hash) {
		return true;
	}
	else {
		return false;
	}
}
