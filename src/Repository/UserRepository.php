<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

class UserRepository
{

    /**
     * Returns all users with any kind of cb admin role.
     * @return mixed
     */
    public static function getCBAdmins() {
        $blogusers = get_users( [ 'role__in' => [ Plugin::$ITEM_ADMIN_ID, Plugin::$LOCATION_ADMIN_ID ] ] );
        return $blogusers;
    }

    /**
     * Returns all users with cb location admin role.
     * @return mixed
     */
    public static function getCBLocationAdmins() {
        $blogusers = get_users( [ 'role__in' => [ Plugin::$LOCATION_ADMIN_ID ] ] );
        return $blogusers;
    }

    /**
     * Returns all users with cb item admin role.
     * @return mixed
     */
    public static function getCBItemAdmins() {
        $blogusers = get_users( [ 'role__in' => [ Plugin::$ITEM_ADMIN_ID ] ] );
        return $blogusers;
    }

}
