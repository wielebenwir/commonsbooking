<?php


namespace CommonsBooking;


class Plugin
{
    /**
     * @return mixed
     */
    public static function getCustomPostTypes()
    {
        return [
            new \CommonsBooking\PostType\Item(),
            new \CommonsBooking\PostType\Location()
        ];
    }

    /**
     *
     */
    public function init() {
        // Register custom post types
        add_action('init', array(self::class, 'registerCustomPostTypes'));

        // Add menu pages
        add_action( 'admin_menu', array(self::class, 'addMenuPages'));

    }

    /**
     *
     */
    public static function addMenuPages() {
        // Dashboard
        add_menu_page(
            'Commons Booking',
            'Commons Booking',
            'manage_options',
            'cb-dashboard',
            array(\CommonsBooking\View\Dashboard::class, 'render')
        );

        /** @var \CommonsBooking\PostType\PostType $cbCustomPostType */
        foreach (self::getCustomPostTypes() as $cbCustomPostType) {
            $params = $cbCustomPostType->getMenuParams();
            add_submenu_page(
                $params[0],
                $params[1],
                $params[2],
                $params[3],
                $params[4]
            );
        }
    }

    /**
     *
     */
    public static function registerCustomPostTypes() {
        foreach (self::getCustomPostTypes() as $customPostType) {
            register_post_type( $customPostType->getPostType(), $customPostType->getArgs() );
        }
    }

}
