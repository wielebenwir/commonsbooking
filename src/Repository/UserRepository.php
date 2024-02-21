<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;
use WP_Query;

class UserRepository {

	/**
	 * Returns all users with role that can be assigned to item / location.
	 * @return mixed
	 */
	public static function getSelectableCBManagers() {
        return get_users( ['role__in' => self::getManagerRoles()] );
	}

	public static function getManagerRoles() : array {
		return apply_filters("commonsbooking_manager_roles",[Plugin::$CB_MANAGER_ID]);
	}

	/**
	 * Will get all roles that are considered by CommonsBooking as "Administrator" roles
	 * @return array
	 */
	public static function getAdminRoles() : array {
		return apply_filters('commonsbooking_admin_roles', ['administrator']);
	}

	/**
	 * Returns all users with items/locations.
	 * @return array
	 */
	public static function getOwners(): array {
		$owners   = [];
		$ownerIds = [];
		$args     = array(
			'post_type' => array(
				\CommonsBooking\Wordpress\CustomPostType\Item::$postType,
				\CommonsBooking\Wordpress\CustomPostType\Location::$postType,
			)
		);
		$query    = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$cbPosts = $query->get_posts();
			foreach ( $cbPosts as $cbPost ) {
				$ownerIds[]       = $cbPost->post_author;
				$additionalAdmins = get_post_meta( $cbPost->ID, '_' . $cbPost->post_type . '_admins', true );
				if ( is_array( $additionalAdmins ) && count( $additionalAdmins ) ) {
					$ownerIds = array_merge( $ownerIds, $additionalAdmins );
				}
			}
		}
		$ownerIds = array_unique( $ownerIds );
		if ( count( $ownerIds ) ) {
			return get_users(
				array( 'include' => $ownerIds )
			);
		}

		return $owners;
	}

	/**
	 * Returns an array of all User Roles as roleID => translated role name
	 *
	 * @return array
	 */
	public static function getUserRoles(): array {
		global $wp_roles;
		if ( $wp_roles === null ){
			return [];
		}
		$rolesArray = $wp_roles->roles;
		$roles      = [];
		foreach ( $rolesArray as $roleID => $value ) {
			if ($roleID == 'administrator') {
				continue;
			}
			$roles[ $roleID ] = translate_user_role( $value['name'] );
		}

		return $roles;
	}

	/**
	 * Checks if user has one of the given roles.
	 * Can either take an array of roles or a single role as string.
	 *
	 * @since 2.9.0
	 *
	 * @param int $userID
	 * @param string|array $roles
	 * @return bool
	 */
	public static function userHasRoles(int $userID, $roles): bool {
		$user = get_userdata( $userID );
		if ( is_array( $roles ) ) {
			return ! empty( array_intersect( $roles, $user->roles ) );
		} else {
			return in_array( $roles, $user->roles );
		}
	}

}
