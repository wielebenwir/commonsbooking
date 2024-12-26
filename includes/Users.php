<?php

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;


/**
 * Checks if current user is allowed to edit custom post.
 *
 * @param $post
 *
 * @return bool
 * @throws Exception
 */
function commonsbooking_isCurrentUserAllowedToEdit( $post ): bool {
	if ( ! is_user_logged_in() ) {
		return false; }

	$current_user = wp_get_current_user();

	return commonsbooking_isUserAllowedToEdit( $post, $current_user );
}

/**
 * Checks if user is allowed to edit custom post.
 *
 * @param $post
 * @param $user
 *
 * @return bool
 * @throws Exception
 */
function commonsbooking_isUserAllowedToEdit( $post, WP_User $user ): bool {

	if ( ! Plugin::isPostCustomPostType( $post ) ) {
		return false;
	}

	$postModel = CustomPostType::getModel( $post );

	// authors are always allowed to edit their posts, admins are also allowed to edit all posts
	if ( $postModel->isAuthor( $user ) || commonsbooking_isUserAdmin( $user ) ) {
		return true;
	}

	$canView = commonsbooking_isUserAllowedToSee( $postModel, $user );
	$canEdit = user_can( $user, 'edit_post', $post->ID );

	return $canView && $canEdit;
}

/**
 * Validates if current user is allowed to edit current post in admin.
 *
 * @param $current_screen
 */
function commonsbooking_validate_user_on_edit( $current_screen ) {
	if ( $current_screen->base == 'post' && in_array( $current_screen->id, Plugin::getCustomPostTypesLabels() ) ) {
		if ( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'edit' ) {
			$post = get_post( intval( $_GET['post'] ) );
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
	// check for CPT before evaluation of permission, use short-circuit to prevent invalid data access
	if ( Plugin::isPostCustomPostType( $post ) && ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
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
			// return when it is not our CPT
			if ( ! in_array( $postType, Plugin::getCustomPostTypesLabels() ) ) {
				return $posts;
			}

			$isAdmin = commonsbooking_isCurrentUserAdmin();

			// Check if it is the main query
			if ( ! $isAdmin && $query->is_main_query() ) {
				foreach ( $posts as $key => $post ) {
					if ( ! commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
						unset( $posts[ $key ] );
					}
				}
			}

			// Save posts to global variable for later use -> fix of counts in admin lists
			if (
				array_key_exists( 'post_type', $_GET ) &&
				is_array( $query->query ) && array_key_exists( 'post_type', $query->query )
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
	if ( commonsbooking_isCurrentUserAdmin() ) {
		return $views;
	}

	global ${'posts' . $postType};
	$timeFramePosts = ${'posts' . $postType};

	$counts = [
		'all' => count( $timeFramePosts ),
	];

	// add counts for differentp states
	foreach ( $timeFramePosts as $post ) {
		if ( ! array_key_exists( $post->post_status, $counts ) ) {
			$counts[ $post->post_status ] = 0;
		}

		++$counts[ $post->post_status ];
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
	if ( ! is_user_logged_in() ) {
		return false; }
	$user = wp_get_current_user();

	$isAdmin = commonsbooking_isUserAdmin( $user );
	/**
	 * Default value if current user is admin.
	 *
	 * @since 2.10.0 add $user param
	 * @since 2.4.3
	 *
	 * @param bool         $isAdmin true or false, if current user is admin
	 * @param null|WP_User $user current user
	 */
	return apply_filters( 'commonsbooking_isCurrentUserAdmin', $isAdmin, $user );
}

/**
 * Will check if user has one of the admin roles and is therefore considered an admin for CB.
 * Admin roles can be extended with the filter commonsbooking_admin_roles.
 *
 * An admin is allowed to edit and see all posts.
 *
 * @param   \WP_User $user
 *
 * @return bool
 */
function commonsbooking_isUserAdmin( \WP_User $user ) {
	foreach ( \CommonsBooking\Repository\UserRepository::getAdminRoles() as $adminRole ) {
		if ( in_array( $adminRole, $user->roles ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Returns whether given user is given the cb manager role
 *
 * @since 2.9.0
 *
 * @param WP_User $user
 * @return bool
 */
function commonsbooking_isUserCBManager( \WP_User $user ): bool {
	return apply_filters( 'commonsbooking_isCurrentUserCBManager', in_array( Plugin::$CB_MANAGER_ID, $user->roles ), $user );
}

// Check if current user has subscriber role
function commonsbooking_isCurrentUserSubscriber() {
	$user = wp_get_current_user();

	return apply_filters( 'commonsbooking_isCurrentUserSubscriber', in_array( 'subscriber', $user->roles ), $user );
}

// check if current user has CBManager role
function commonsbooking_isCurrentUserCBManager() {

	$user = wp_get_current_user();

	$isManager = ! empty( array_intersect( \CommonsBooking\Repository\UserRepository::getManagerRoles(), $user->roles ) );

	return apply_filters( 'commonsbooking_isCurrentUserCBManager', $isManager, $user );
}

/**
 * Returns true if user is allowed to book based on the timeframe configuration (user role)
 *
 * @param mixed $timeframeID
 *
 * @return bool
 */
function commonsbooking_isCurrentUserAllowedToBook( $timeframeID ): bool {
	$allowedUserRoles = get_post_meta( $timeframeID, \CommonsBooking\Model\Timeframe::META_ALLOWED_USER_ROLES, true );

	if ( empty( $allowedUserRoles ) || ( commonsbooking_isCurrentUserAdmin() ) ) {
		return true;
	}

	$current_user = wp_get_current_user();
	$user_roles   = $current_user->roles;

	$match = array_intersect( $user_roles, $allowedUserRoles );

	return count( $match ) > 0;
}

/**
 * Determines whether a user may read the current post.
 *
 * It only makes sense to check this with booking posts as all CPTs are / should be public.
 *
 * @param $booking - A booking of the cb_booking type
 *
 * @return void
 */
function commonsbooking_isCurrentUserAllowedToSee( $booking ): bool {
	if ( ! is_user_logged_in() ) {
		return false; }
	if ( ! $booking ) {
		return false;
	}

	$user = wp_get_current_user();

	if ( $user ) {
		return commonsbooking_isUserAllowedToSee( $booking, $user );
	} else {
		return false;
	}
}

/**
 * Determines whether a user is able to read the current post.
 *
 * It only makes sense to check this directly with booking posts as all CPTs are / should be public.
 * It is, however used as a helper function for commonsbooking_isCurrentUserAllowedToEdit.
 * We apply the logic, that only something that is allowed to be seen may be edited.
 *
 * @param \CommonsBooking\Model\Booking|WP_Post|int $post
 * @param WP_User                                   $user
 *
 * @return bool
 */
function commonsbooking_isUserAllowedToSee( $post, WP_User $user ): bool {

	if ( ! $post instanceof \CommonsBooking\Model\CustomPost ) {
		if ( ! Plugin::isPostCustomPostType( $post ) ) {
			return false;
		}

		try {
			$postModel = CustomPostType::getModel( $post );
		} catch ( Exception $e ) {
			return false;
		}
	} else {
		$postModel = $post;
	}

	$isAuthor  = $postModel->isAuthor( $user );
	$isAdmin   = commonsbooking_isUserAdmin( $user );
	$isAllowed = $isAdmin || $isAuthor;

	if ( ! $isAllowed ) {
		$admins    = $postModel->getAdmins();
		$isAllowed = ( is_string( $admins ) && $user->ID == $admins ) ||
					( is_array( $admins ) && in_array( $user->ID, $admins, true ) );
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
function commonsbooking_isUIDHashComboCorrect( $user_id, $user_hash ): bool {
	if ( wp_hash( $user_id ) == $user_hash ) {
		return true;
	} else {
		return false;
	}
}
