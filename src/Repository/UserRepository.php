<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;

class UserRepository
{

    /**
     * Returns all users with cb manager role.
     * @return mixed
     */
    public static function getCBManagers()
    {
        $blogusers = get_users(['role__in' => [Plugin::$CB_MANAGER_ID]]);

        return $blogusers;
    }

    /**
     * Returns all users with items/locations.
     * @return array
     */
    public static function getOwners()
    {
        $owners = [];
        $ownerIds = [];
        $args = array(
            'post_type' => array(
                \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
                \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
            )
        );
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $cbPosts = $query->get_posts();
            foreach ($cbPosts as $cbPost) {
                $ownerIds[] = $cbPost->post_author;
                $additionalAdmins = get_post_meta($cbPost->ID, '_' . $cbPost->post_type . '_admins', true);
                if (is_array($additionalAdmins) && count($additionalAdmins)) {
                    $ownerIds = array_merge($ownerIds, $additionalAdmins);
                }
            }
        }
        $ownerIds = array_unique($ownerIds);
        if (count($ownerIds)) {
            return get_users(
                array('include' => $ownerIds)
            );
        }
        return $owners;
    }

}
