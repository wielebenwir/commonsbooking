<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;

class Item extends CustomPostType
{

    /**
     * @var string
     */
    public static $postType = 'cb_item';

    /**
     * Item constructor.
     */
    public function __construct()
    {
        add_filter('the_content', array($this, 'getTemplate'));
        add_action('cmb2_admin_init', array($this, 'registerMetabox'));

        // Listing of locations for item
        add_shortcode('cb_locations', array(\CommonsBooking\View\Location::class, 'shortcode'));

        // Setting role permissions
        add_action('admin_init', array($this, 'addRoleCaps'), 999);

        // Filter only for current user allowed posts
        add_action('pre_get_posts', array($this, 'filterAdminList'));
    }

    /**
     * Filters admin list by type (e.g. bookable, repair etc. )
     *
     * @param  (wp_query object) $query
     *
     * @return Void
     */
    public static function filterAdminList($query)
    {
        global $pagenow;

        if (
            is_admin() && $query->is_main_query() &&
            isset($_GET['post_type']) && self::$postType == $_GET['post_type'] &&
            $pagenow == 'edit.php'
        ) {
            $current_user = wp_get_current_user();
            $isAdmin      = false;
            if (in_array('administrator', (array)$current_user->roles)) {
                $isAdmin = true;
            }

            // Check if current user is allowed to see posts
            if ( ! $isAdmin) {
                $items = \CommonsBooking\Repository\Item::getByCurrentUser();
                array_walk(
                    $items,
                    function (&$item, $key) {
                        $item = $item->ID;
                    }
                );
                $query->query_vars['post__in'] = $items;
            }
        }
    }

    public static function getView()
    {
        return new \CommonsBooking\View\Item();
    }

    /**
     * Returns CPT args.
     * @return array|mixed
     */
    public function getArgs()
    {
        $labels = array(
            'name'                  => __('Items', 'commonsbooking'),
            'singular_name'         => __('Item', 'commonsbooking'),
            'add_new'               => __('Add new', 'commonsbooking'),
            'add_new_item'          => __('Add new item', 'commonsbooking'),
            'edit_item'             => __('Edit item', 'commonsbooking'),
            'new_item'              => __('Add new item', 'commonsbooking'),
            'view_item'             => __('Show item', 'commonsbooking'),
            'view_items'            => __('Show items', 'commonsbooking'),
            'search_items'          => __('Search items', 'commonsbooking'),
            'not_found'             => __('items not found', 'commonsbooking'),
            'not_found_in_trash'    => __('No items found in trash', 'commonsbooking'),
            'parent_item_colon'     => __('Parent items:', 'commonsbooking'),
            'all_items'             => __('All items', 'commonsbooking'),
            'archives'              => __('Item archive', 'commonsbooking'),
            'attributes'            => __('Item attributes', 'commonsbooking'),
            'insert_into_item'      => __('Add to item', 'commonsbooking'),
            'uploaded_to_this_item' => __('Added to item', 'commonsbooking'),
            'featured_image'        => __('Item image', 'commonsbooking'),
            'set_featured_image'    => __('set item image', 'commonsbooking'),
            'remove_featured_image' => __('remove item image', 'commonsbooking'),
            'use_featured_image'    => __('use as item image', 'commonsbooking'),
            'menu_name'             => __('Items', 'commonsbooking'),

        );

        $slug = Settings::getOption('commonsbooking_options_general', 'posttypes_items-slug');

        // args for the new post_type
        return array(
            'labels'            => $labels,

            // Sichtbarkeit des Post Types
            'public'            => true,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'           => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'      => false,

            // Position im Menu
            'menu_position'     => 3,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar' => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus' => true,

            // Hier können Berechtigungen in einem Array gesetzt werden
            // oder die standart Werte post und page in form eines Strings gesetzt werden
            'capability_type'   => array(self::$postType, self::$postType.'s'),

            'map_meta_cap'        => true,

            // Soll es im Frontend abrufbar sein?
            'publicly_queryable'  => true,

            // Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
            'exclude_from_search' => true,

            // Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'custom-fields',
                'revisions',
                'excerpt',
            ),

            // Soll der Post Type Kategorien zugeordnet werden können?
            'has_archive'         => false,

            // Soll man den Post Type exportieren können?
            'can_export'          => false,

            // Slug unseres Post Types für die redirects
            // dieser Wert wird später in der URL stehen
            'rewrite'             => array('slug' => $slug),

            'show_in_rest' => true,
        );
    }

    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            ob_start();
            global $post;
            $item = new \CommonsBooking\Model\Item($post);
            set_query_var('item', $item);
            commonsbooking_get_template_part('item', 'single');
            $cb_content = ob_get_clean();
        } // if archive...

        return $content.$cb_content;
    }

    /**
     * Creates MetaBoxes for Custom Post Type Location using CMB2
     * more information on usage: https://cmb2.io/
     *
     * @return void
     */
    public function registerMetabox()
    {
        // Initiate the metabox Adress
        $cmb = new_cmb2_box(
            array(
                'id'           => COMMONSBOOKING_METABOX_PREFIX.'item_info',
                'title'        => __('Item Info', 'commonsbooking'),
                'object_types' => array(self::$postType), // Post type
                'context'      => 'normal',
                'priority'     => 'high',
                'show_names'   => true, // Show field names on the left
            )
        );

        $users       = UserRepository::getCBManagers();
        $userOptions = [];
        foreach ($users as $user) {
            $userOptions[$user->ID] = $user->get('user_nicename')." (".$user->last_name." ".$user->last_name.")";
        }

        $cmb->add_field(
            array(
                'name'       => __('Item Admin(s)', 'commonsbooking'),
                'desc'       => __(
                    'choose one or more users to give them the permisssion to edit and manage this specific item. Only users with the role cb_manager can be selected here',
                    'commonsbooking'
                ),
                'id'         => COMMONSBOOKING_METABOX_PREFIX.'item_admins',
                'type'       => 'pw_multiselect',
                'options'    => $userOptions,
                'attributes' => array(
                    'placeholder' => __('Select item admins.', 'commonsbooking'),
                ),
            )
        );

    }
}
