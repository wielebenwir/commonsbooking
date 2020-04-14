<?php


namespace CommonsBooking;


class Plugin
{

    public static function addMenuPages() {
        // Add menu pages
        add_action( 'admin_menu', 'cb_menuitems' );
        function cb_menuitems() {
            add_menu_page(
                'Commons Booking',
                'Commons Booking',
                'manage_options',
                'cb-dashboard',
                array(\CommonsBooking\View\Dashboard::class, 'render')

            );

            $cbCustomPostTypes = [
                new \CommonsBooking\PostType\Item(),
                new \CommonsBooking\PostType\Location()
            ];

            /** @var \CommonsBooking\PostType\PostType $cbCustomPostType */
            foreach ($cbCustomPostTypes as $cbCustomPostType) {
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
    }

}
