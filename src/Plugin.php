<?php


namespace CommonsBooking;

use CommonsBooking\Controller\TimeframeController;
use CommonsBooking\Model\Booking;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\CB1UserFields;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Wordpress\PostStatus\PostStatus;
use CommonsBooking\Model\User;
use CommonsBooking\Wordpress\Options;
use CB;
use CommonsBooking\Migration\Migration;
use CommonsBooking\Wordpress\Options\AdminOptions;

class Plugin
{

    /**
     * CB-Manager id.
     * @var string
     */
    public static $CB_MANAGER_ID = 'cb_manager';

    /**
     * Deletes cb transients.
     * @param $param
     */
    public static function clearCache($param = "") {
        global $wpdb;
        $sql = "
            DELETE 
            FROM {$wpdb->options}
            WHERE option_name like '_transient_commonsbooking%".$param."%'
        ";
        $wpdb->query($sql);
    }

    /**
     * Returns cache id, based on calling class, function and args.
     * @param null $custom_id
     *
     * @return string
     */
    public static function getCacheId($custom_id = null) {
        $backtrace = debug_backtrace()[2];
        $namespace = str_replace('\\','_', strtolower($backtrace['class']));
        $namespace .= '_'. $backtrace['function'];
        $namespace .= '_'. md5( serialize($backtrace['args']));
        if($custom_id) {
            $namespace .= $custom_id;
        }
        return $namespace;
    }

    /**
     * Returns cache item based on calling class, function and args.
     * @param null $custom_id
     *
     * @return mixed
     */
    public static function getCacheItem($custom_id = null) {
        return get_transient(self::getCacheId($custom_id));
    }

    /**
     * Saves cache item based on calling class, function and args.
     * @param $value
     * @param null $custom_id
     *
     * @return mixed
     */
    public static function setCacheItem($value, $custom_id = null) {
        return set_transient(self::getCacheId($custom_id), $value);
    }

    /**
     *  Init hooks.
     */
    public function init()
    {

        do_action('cmb2_init');

        // Register custom user roles (e.g. location-owner, item-owner etc.)
        add_action('admin_init', array(self::class, 'addCustomUserRoles'));

        // Enable CB1 User Fields (needed in case of migration from cb 0.9.x)
        add_action('init', array(self::class, 'maybeEnableCB1UserFields'));

        // Register custom post types
        add_action('init', array(self::class, 'registerCustomPostTypes'), 0);
        add_action('init', array(self::class, 'registerPostStates'), 0);

        // register admin options page
        add_action('init', array(self::class, 'registerAdminOptions'), 0);

        // Register custom post types taxonomy / categories
        add_action('init', array(self::class, 'registerItemTaxonomy'), 30);

        // Register custom post types taxonomy / categories
        add_action('init', array(self::class, 'registerLocationTaxonomy'), 30);

        // check if we have a new version and run tasks
        add_action( 'admin_init', array( self::class, 'runTasksAfterUpdate' ), 30 );

        // Add menu pages
        add_action('admin_menu', array(self::class, 'addMenuPages'));

        // Parent Menu Fix
        add_filter('parent_file', array($this, "setParentFile"));

        // Remove cache items on save.
        add_action( 'save_post', array( $this, 'savePostActions' ), 10, 2 );

        // actions after saving plugin options
        add_action( 'admin_init', array (self::class, 'saveOptionsActions'), 100 );
    }

    /**
     * Removes cache item in connection to post_type.
     * @param $post_id
     * @param $post
     */
    public function savePostActions($post_id, $post)
    {
        if (!in_array($post->post_type, self::getCustomPostTypesLabels())) {
            return;
        }
//        self::clearCache(str_replace('cb_','', $post->post_type));
        self::clearCache();

        // Remove cache for timeframe repos
        if($post->post_type == Timeframe::$postType) {
            self::clearCache('book');
        }
    }

    /**
     * Function to register our new routes from the controller.
     */
    public function initRoutes() {
        add_action(
            'rest_api_init',
            function () {
                $routes = [
                    new \CommonsBooking\API\AvailabilityRoute(),
                    new \CommonsBooking\API\ItemsRoute(),
                    new \CommonsBooking\API\LocationsRoute(),
                    new \CommonsBooking\API\OwnersRoute(),
                    new \CommonsBooking\API\ProjectsRoute()

                ];
                foreach($routes as $route) {
                    $route->register_routes();
                }
            }
        );
    }

    /**
     * Adds bookingcode actions.
     */
    public function initBookingcodes() {
        add_action( 'before_delete_post', array(BookingCodes::class,'deleteBookingCodes'), 10 );
        add_action( "admin_action_csvexport", array(\CommonsBooking\View\BookingCodes::class, 'renderCSV'), 10, 0 );
    }

    /**
     * @return mixed
     */
    public static function getCustomPostTypes()
    {
        return [
            new Item(),
            new Location(),
            new Timeframe(),
            new Map()
        ];
    }

    /**
     * @return mixed
     */
    public static function getCustomPostTypesLabels()
    {
        return [
            Item::getPostType(),
            Location::getPostType(),
            Timeframe::getPostType(),
            Map::getPostType()
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
     *
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
            'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
            'cb-dashboard',
            array(\CommonsBooking\View\Dashboard::class, 'index'),
            'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"><path fill="black" d="M12.94,5.68l0,-5.158l6.132,1.352l0,5.641c0.856,-0.207 1.787,-0.31 2.792,-0.31c3.233,0 5.731,1.017 7.493,3.05c1.762,2.034 2.643,4.661 2.643,7.88l0,0.458c0,3.232 -0.884,5.862 -2.653,7.89c-1.769,2.027 -4.283,3.04 -7.542,3.04c-1.566,0 -2.965,-0.268 -4.196,-0.806c1.449,-1.329 2.491,-2.998 3.015,-4.546c0.335,0.123 0.729,0.185 1.181,0.185c1.311,0 2.222,-0.51 2.732,-1.53c0.51,-1.021 0.765,-2.432 0.765,-4.233l0,-0.458c0,-1.749 -0.255,-3.146 -0.765,-4.193c-0.51,-1.047 -1.401,-1.57 -2.673,-1.57c-0.527,0 -0.978,0.107 -1.351,0.321c-1.051,-3.59 -4.047,-6.125 -7.573,-7.013Zm6.06,15.774c0.05,0.153 0.042,0.325 0.042,0.338c-0.001,2.138 -0.918,4.209 -2.516,5.584c-0.172,0.148 -0.346,0.288 -0.523,0.42c-0.209,-0.153 -0.411,-0.316 -0.608,-0.489c-1.676,-1.477 -2.487,-3.388 -2.434,-5.733l0.039,-0.12l6,0Zm-6.06,-13.799c3.351,1.058 5.949,3.88 6.092,7.332c0.011,0.254 0.11,0.416 -0.032,0.843l-6,0l-0.036,-0.108l-0.024,0l0,-8.067Z" /><path fill="black" d="M21.805,24.356c-0.901,0 -1.57,-0.245 -2.008,-0.735c-0.437,-0.491 -0.656,-1.213 -0.656,-2.167l-6.141,0l-0.039,0.12c-0.053,2.345 0.758,4.256 2.434,5.733c1.676,1.478 3.813,2.216 6.41,2.216c3.259,0 5.773,-1.013 7.542,-3.04c1.769,-2.028 2.653,-4.658 2.653,-7.89l0,-0.458c0,-3.219 -6.698,-1.749 -6.698,0l0,0.458c0,1.801 -0.255,3.212 -0.765,4.233c-0.51,1.02 -1.421,1.53 -2.732,1.53Z" /><path fill="black" d="M14.244,28.78c-1.195,0.495 -2.545,0.743 -4.049,0.743c-3.259,0 -5.773,-1.013 -7.542,-3.04c-1.769,-2.028 -2.653,-4.658 -2.653,-7.89l0,-0.458c0,-3.219 0.881,-5.846 2.643,-7.88c1.762,-2.033 4.26,-3.05 7.493,-3.05c0.917,0 1.773,0.086 2.566,0.258c1.566,0.34 2.891,1.016 3.972,2.027c1.63,1.524 2.418,3.597 2.365,6.221l-0.039,0.119l-6.141,0c0,-1.02 -0.226,-1.852 -0.676,-2.494c-0.451,-0.643 -1.133,-0.964 -2.047,-0.964c-1.272,0 -2.163,0.523 -2.673,1.57c-0.51,1.047 -0.765,2.444 -0.765,4.193l0,0.458c0,1.801 0.255,3.212 0.765,4.233c0.51,1.02 1.421,1.53 2.732,1.53c0.32,0 0.61,-0.031 0.871,-0.093c0.517,1.648 1.73,3.281 3.178,4.517Zm-1.244,-7.326l6,0l0.039,0.12c0.053,2.345 -0.758,4.256 -2.434,5.733c-0.134,0.118 -0.27,0.231 -0.409,0.339c-1.85,-1.327 -3.122,-3.233 -3.227,-5.424c-0.011,-0.228 -0.105,-0.357 0.031,-0.768Z" /></svg>')
        );
        add_submenu_page(
            'cb-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
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
    public static function registerPostStates()
    {
        foreach (Booking::$bookingStates as $bookingState) {
            new PostStatus($bookingState, __(ucfirst($bookingState), 'commonsbooking'));
        }
    }

    /**
     * Registers category taxonomy for Custom Post Type Item
     * @return void
     */
    public static function registerItemTaxonomy()
    {
        $customPostType = Item::getPostType();

        $result = register_taxonomy(
            $customPostType . 's_category',
            $customPostType,
            array(
                'label'        => esc_html__('Item Category', 'commonsbooking'),
                'rewrite'      => array('slug' => $customPostType . '-cat'),
                'hierarchical' => true,
                'show_in_rest' => true,
            )
        );

        // If error, yell about it.
        if ( is_wp_error( $result ) ) {
            wp_die( $result->get_error_message() );
        }
    }

    /**
     * Registers category taxonomy for Custom Post Type Location
     * @return void
     */
    public static function registerLocationTaxonomy()
    {
        $customPostType = Location::getPostType();

        $result = register_taxonomy(
            $customPostType . 's_category',
            $customPostType,
            array(
                'label'        => esc_html__('Location Category', 'commonsbooking'),
                'rewrite'      => array('slug' => $customPostType . '-cat'),
                'hierarchical' => true,
                'show_in_rest' => true,
            )
        );

        // If error, yell about it.
        if ( is_wp_error( $result ) ) {
            wp_die( $result->get_error_message() );
        }
    }

    /**
     * Adds cb user roles to wordpress.
     */
    public static function addCustomUserRoles()
    {
        $cbPostTypeNames = [];
        foreach (Plugin::getCustomPostTypes() as $postType) {
            $cbPostTypeNames[] = $postType::$postType;
        };

        $roleCapMapping = [
            Plugin::$CB_MANAGER_ID     => [
                'read'                     => true,
                'manage_' . COMMONSBOOKING_PLUGIN_SLUG => true
            ],
            'administrator'            => [
                'read'                     => true,
                'edit_posts'               => true,
                'manage_' . COMMONSBOOKING_PLUGIN_SLUG => true
            ]
        ];

        foreach ($roleCapMapping as $roleName => $caps) {
            $role = get_role($roleName);
            if ( ! $role) {
                $role = add_role(
                    $roleName,
                    __($roleName, COMMONSBOOKING_PLUGIN_SLUG)
                );
            }

            foreach ($caps as $cap => $grant) {
                $role->remove_cap($cap);
                $role->add_cap($cap, $grant);
            }
        }
    }

    /**
     * Renders error for backend_notice.
     */
    public static function renderError()
    {
        $errorTypes = [
            \CommonsBooking\Model\Timeframe::ERROR_TYPE,
            BookingCode::ERROR_TYPE,
        ];

        foreach ($errorTypes as $errorType) {
            if ($error = get_transient($errorType)) {
                $class = 'notice notice-error';
                printf(
                    '<div class="%1$s"><p>%2$s</p></div>',
                    esc_attr($class),
                    esc_html($error)
                );
                delete_transient($errorType);
            }
        }
    }

    /**
     * Enable Legacy CB1 profile fields.
     */
    public static function maybeEnableCB1UserFields()
    {
        $enabled = Settings::getOption('commonsbooking_options_migration', 'enable-cb1-user-fields');
        if ( $enabled == 'on') {
            new CB1UserFields;
        }
    }


    /**
     * run actions after plugin options are saved
     */
    public static function saveOptionsActions()
    {
        if ( get_transient('commonsbooking_options_saved') == 1) {
            // restore default values if necessary
            AdminOptions::SetOptionsDefaultValues();

            // flush rewrite rules to get permalinks working
            flush_rewrite_rules();

            set_transient('commonsbooking_options_saved', 0);
        }
    }


     /**
     * Register Admin-Options
     */
    public static function registerAdminOptions()
    {
        $options_array = include(COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php');
        foreach ($options_array as $tab_id => $tab) {
            new \CommonsBooking\Wordpress\Options\OptionsTab($tab_id, $tab);
        }
    }

    /**
     * Check if plugin is installed or updated an run tasks
     */
    public static function runTasksAfterUpdate() {

        $commonsbooking_version_option = COMMONSBOOKING_PLUGIN_SLUG . '_plugin_version';
        $commonsbooking_installed_version = get_option ( $commonsbooking_version_option );


        // check if installed version differs from plugin version in database
        if ( COMMONSBOOKING_VERSION !== $commonsbooking_installed_version OR !isset( $commonsbooking_installed_version ) ) {

            // set Options default values (e.g. if there are new fields added)
            AdminOptions::SetOptionsDefaultValues();

            // flush rewrite rules
            flush_rewrite_rules();

            // add more tasks if necessary
            // ...

            // update version number in options
            update_option( $commonsbooking_version_option, COMMONSBOOKING_VERSION );
        }
    }

}
