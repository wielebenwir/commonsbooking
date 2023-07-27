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
        $managerRoles = [ Plugin::$CB_MANAGER_ID ];
        $managerRoles = apply_filters( "commonsbooking_manager_roles" , $managerRoles );
		return get_users( [ 'role__in' => $managerRoles ]  );
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
		$rolesArray = $wp_roles->roles;
		$roles      = [];
		foreach ( $rolesArray as $roleID => $value ) {
			$roles[ $roleID ] = translate_user_role( $value['name'] );
		}

		return $roles;
	}

}
