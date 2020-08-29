<?php


namespace CommonsBooking;

use CommonsBooking\Controller\TimeframeController;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Wordpress\PostStatus\PostStatus;
use CommonsBooking\Model\User;
use CB;

class Plugin
{

    public static $LOCATION_ADMIN_ID = 'location_admin';
    public static $ITEM_ADMIN_ID = 'item_admin';
    public static $CB_MANAGER_ID = 'cb_manager';

    /**
     *  Init hooks.
     */
    public function init()
    {
        do_action( 'cmb2_init' );

        // Register custom post types taxonomy / categories
        add_action('init', array(self::class, 'registerItemTaxonomy'));

        // Register custom user roles (e.g. location-owner, item-owner etc.)
        add_action('admin_init', array(self::class, 'addCustomUserRoles'));

        // Register custom post types
        add_action('init', array(self::class, 'registerCustomPostTypes'));
        add_action('init', array(self::class, 'registerPostStatuses'));

        // Add menu pages
        add_action('admin_menu', array(self::class, 'addMenuPages'));

        // Parent Menu Fix
        add_filter('parent_file', array($this, "setParentFile"));
    }

    /**
     * @return mixed
     */
    public static function getCustomPostTypes()
    {
        return [
            new Item(),
            new Location(),
            new Timeframe()
        ];
    }

    /**
     * @return mixed
     */
    public static function getCustomPostTypesLabel()
    {
        return [
            Item::getPostType(),
            Location::getPostType(),
            Timeframe::getPostType()
        ];
    }

    /**
     * Fixes highlighting issue for cpt views.
     *
     * @param $parent_file
     *
     * @return string
     */
    public function setParentFile($parent_file)
    {
        global $current_screen;

        // Set 'cb-dashboard' as parent for cb post types
        if (in_array($current_screen->base, array('post', 'edit'))) {
            foreach (self::getCustomPostTypes() as $customPostType) {
                if ($customPostType::getPostType() === $current_screen->post_type) {
                    return 'cb-dashboard';
                }
            }
        }

        return $parent_file;
    }

    /**
     * Appends view data to content.
     * @param $content
     *
     * @return string
     */
    public function getTheContent($content)
    {
        // Check if we're inside the main loop in a single post page.
        if (is_single() && in_the_loop() && is_main_query()) {
            global $post;

            /** @var PostType $customPostType */
            foreach (self::getCustomPostTypes() as $customPostType) {
                if ($customPostType::getPostType() === $post->post_type) {
                    return $content . $customPostType::getView()::content($post);
                }
            }
        }

        return $content;
    }

    /**
     * Adds menu pages.
     */
    public static function addMenuPages()
    {
        // Dashboard
        add_menu_page(
            'CommonsBooking',
            'CommonsBooking',
            'manage_' . CB_PLUGIN_SLUG,
            'cb-dashboard',
            array(\CommonsBooking\View\Dashboard::class, 'index'),
            'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"><path fill="black" d="M12.94,5.68l0,-5.158l6.132,1.352l0,5.641c0.856,-0.207 1.787,-0.31 2.792,-0.31c3.233,0 5.731,1.017 7.493,3.05c1.762,2.034 2.643,4.661 2.643,7.88l0,0.458c0,3.232 -0.884,5.862 -2.653,7.89c-1.769,2.027 -4.283,3.04 -7.542,3.04c-1.566,0 -2.965,-0.268 -4.196,-0.806c1.449,-1.329 2.491,-2.998 3.015,-4.546c0.335,0.123 0.729,0.185 1.181,0.185c1.311,0 2.222,-0.51 2.732,-1.53c0.51,-1.021 0.765,-2.432 0.765,-4.233l0,-0.458c0,-1.749 -0.255,-3.146 -0.765,-4.193c-0.51,-1.047 -1.401,-1.57 -2.673,-1.57c-0.527,0 -0.978,0.107 -1.351,0.321c-1.051,-3.59 -4.047,-6.125 -7.573,-7.013Zm6.06,15.774c0.05,0.153 0.042,0.325 0.042,0.338c-0.001,2.138 -0.918,4.209 -2.516,5.584c-0.172,0.148 -0.346,0.288 -0.523,0.42c-0.209,-0.153 -0.411,-0.316 -0.608,-0.489c-1.676,-1.477 -2.487,-3.388 -2.434,-5.733l0.039,-0.12l6,0Zm-6.06,-13.799c3.351,1.058 5.949,3.88 6.092,7.332c0.011,0.254 0.11,0.416 -0.032,0.843l-6,0l-0.036,-0.108l-0.024,0l0,-8.067Z" /><path fill="black" d="M21.805,24.356c-0.901,0 -1.57,-0.245 -2.008,-0.735c-0.437,-0.491 -0.656,-1.213 -0.656,-2.167l-6.141,0l-0.039,0.12c-0.053,2.345 0.758,4.256 2.434,5.733c1.676,1.478 3.813,2.216 6.41,2.216c3.259,0 5.773,-1.013 7.542,-3.04c1.769,-2.028 2.653,-4.658 2.653,-7.89l0,-0.458c0,-3.219 -6.698,-1.749 -6.698,0l0,0.458c0,1.801 -0.255,3.212 -0.765,4.233c-0.51,1.02 -1.421,1.53 -2.732,1.53Z" /><path fill="black" d="M14.244,28.78c-1.195,0.495 -2.545,0.743 -4.049,0.743c-3.259,0 -5.773,-1.013 -7.542,-3.04c-1.769,-2.028 -2.653,-4.658 -2.653,-7.89l0,-0.458c0,-3.219 0.881,-5.846 2.643,-7.88c1.762,-2.033 4.26,-3.05 7.493,-3.05c0.917,0 1.773,0.086 2.566,0.258c1.566,0.34 2.891,1.016 3.972,2.027c1.63,1.524 2.418,3.597 2.365,6.221l-0.039,0.119l-6.141,0c0,-1.02 -0.226,-1.852 -0.676,-2.494c-0.451,-0.643 -1.133,-0.964 -2.047,-0.964c-1.272,0 -2.163,0.523 -2.673,1.57c-0.51,1.047 -0.765,2.444 -0.765,4.193l0,0.458c0,1.801 0.255,3.212 0.765,4.233c0.51,1.02 1.421,1.53 2.732,1.53c0.32,0 0.61,-0.031 0.871,-0.093c0.517,1.648 1.73,3.281 3.178,4.517Zm-1.244,-7.326l6,0l0.039,0.12c0.053,2.345 -0.758,4.256 -2.434,5.733c-0.134,0.118 -0.27,0.231 -0.409,0.339c-1.85,-1.327 -3.122,-3.233 -3.227,-5.424c-0.011,-0.228 -0.105,-0.357 0.031,-0.768Z" /></svg>')
        );
        add_submenu_page(
            'cb-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_' . CB_PLUGIN_SLUG,
            'cb-dashboard',
            array(\CommonsBooking\View\Dashboard::class, 'index'),
            0
        );

        // Custom post types
        foreach (self::getCustomPostTypes() as $cbCustomPostType) {
            $params = $cbCustomPostType->getMenuParams();
            add_submenu_page(
                $params[0],
                $params[1],
                $params[2],
                $params[3] . '_' . $cbCustomPostType::$postType,
                $params[4],
                $params[5],
                $params[6]
            );
        }
    }

    /**
     * Registers custom post types.
     */
    public static function registerCustomPostTypes()
    {
        /** @var PostType $customPostType */
        foreach (self::getCustomPostTypes() as $customPostType) {
            register_post_type($customPostType::getPostType(), $customPostType->getArgs());
            $customPostType->initListView();
        }
    }

    /**
     * Registers additional post statuses.
     */
    public static function registerPostStatuses()
    {
        $cancelled = new PostStatus("cancelled", __('Cancelled', 'commonsbooking'));
        $confirmed = new PostStatus("confirmed", __('Confirmed', 'commonsbooking'));
        $unconfirmed = new PostStatus("unconfirmed", __('Unconfirmed', 'commonsbooking'));
    }

    /**
     * Registers category taxonomy for Custom Post Type Item
     * @return void
     */
    public static function registerItemTaxonomy()
    {
        $customPostType = Item::getPostType();

        register_taxonomy(
            $customPostType . '_category',
            $customPostType,
            array(
                'label' => __('Item Category', 'commonsbooking'),
                'rewrite' => array('slug' => $customPostType . '-cat'),
                'hierarchical' => true,
            )
        );
    }

    public static function addCustomUserRoles()
    {
        $cbPostTypeNames = [];
        foreach(Plugin::getCustomPostTypes() as $postType) {
            $cbPostTypeNames[] = $postType::$postType;
        };

        $roleCapMapping = [
            Plugin::$LOCATION_ADMIN_ID => [
                'read' => true,
                'manage_' . CB_PLUGIN_SLUG => true
            ],
            Plugin::$ITEM_ADMIN_ID => [
                'read' => true,
                'manage_' . CB_PLUGIN_SLUG => true
            ],
            Plugin::$CB_MANAGER_ID => [
                'read' => true,
                'manage_' . CB_PLUGIN_SLUG => true
            ],
            'administrator' => [
                'read' => true,
                'edit_posts' => true,
                'manage_' . CB_PLUGIN_SLUG => true
            ]
        ];

        foreach ($roleCapMapping as $roleName => $caps) {
            $role = get_role( $roleName );
            if(!$role) {
                $role = add_role(
                    $roleName,
                    __( $roleName, CB_PLUGIN_SLUG )
                );
            }

            foreach ($caps as $cap => $grant) {
                $role->remove_cap($cap);
                $role->add_cap($cap, $grant);
            }
        }
    }

}
