<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\UserRepository;

class Location extends CustomPostType
{

    public static $postType = 'cb_location';

    /**
     * Item constructor.
     */
    public function __construct()
    {
        add_filter('the_content', array($this, 'getTemplate'));
        add_action('cmb2_admin_init', array($this, 'registerMetabox'));

        // Listing of items for location
        add_shortcode('cb_items', array(\CommonsBooking\View\Item::class, 'listItems'));

        // Setting role permissions
        add_action('admin_init',array($this, 'addRoleCaps'),999);
    }

    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            ob_start();
            cb_get_template_part('location', 'single');
            $cb_content = ob_get_clean();
        } // if archive...

        return $content . $cb_content;
    }

    public function getArgs()
    {
        $labels = array(
            'name'                  => __('Locations', 'commonsbooking'),
            'singular_name'         => __('Location', 'commonsbooking'),
            'add_new'               => __('Add new', 'commonsbooking'),
            'add_new_item'          => __('Add new location', 'commonsbooking'),
            'edit_item'             => __('Edit location', 'commonsbooking'),
            'new_item'              => __('Add new location', 'commonsbooking'),
            'view_item'             => __('Show location', 'commonsbooking'),
            'view_items'            => __('Show locations', 'commonsbooking'),
            'search_items'          => __('Search locations', 'commonsbooking'),
            'not_found'             => __('location not found', 'commonsbooking'),
            'not_found_in_trash'    => __('No locations found in trash', 'commonsbooking'),
            'parent_item_colon'     => __('Parent location:', 'commonsbooking'),
            'all_items'             => __('All locations', 'commonsbooking'),
            'archives'              => __('Location archive', 'commonsbooking'),
            'attributes'            => __('Location attributes', 'commonsbooking'),
            'insert_into_item'      => __('Add to location', 'commonsbooking'),
            'uploaded_to_this_item' => __('Added to location', 'commonsbooking'),
            'featured_image'        => __('Location image', 'commonsbooking'),
            'set_featured_image'    => __('set location image', 'commonsbooking'),
            'remove_featured_image' => __('remove location image', 'commonsbooking'),
            'use_featured_image'    => __('use as location image', 'commonsbooking'),
            'menu_name'             => __('Locations', 'commonsbooking'),
        );

        // args for the new post_type
        return array(
            'labels'              => $labels,

            // Sichtbarkeit des Post Types
            'public'              => true,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'             => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'        => false,

            // Position im Menu
            'menu_position'       => 4,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar'   => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus'   => true,

            // Hier können Berechtigungen in einem Array gesetzt werden
            // oder die standart Werte post und page in form eines Strings gesetzt werden
            'capability_type'     => array(self::$postType,self::$postType . 's'),

            'map_meta_cap'        => true,

            // Soll es im Frontend abrufbar sein?
            'publicly_queryable'  => true,

            // Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
            'exclude_from_search' => true,

            // Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
            'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions', 'excerpt'),

            // Soll der Post Type Kategien haben?
            //'taxonomies'         => array('category'),

            // Soll der Post Type Archiv-Seiten haben?
            'has_archive'         => false,

            // Soll man den Post Type exportieren können?
            'can_export'          => false,

            // Slug unseres Post Types für die redirects
            // dieser Wert wird später in der URL stehen
            'rewrite'             => array('slug' => self::getPostType())
        );
    }

    public static function getView()
    {
        return new \CommonsBooking\View\Location();
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
        $cmb = new_cmb2_box(array(
            'id'           => CB_METABOX_PREFIX . 'location_adress',
            'title'        => __('Address', 'commonsbooking'),
            'object_types' => array('cb_location',), // Post type
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            // 'closed'     => true, // Keep the metabox closed by default
        ));

        // Adress
        $cmb->add_field(array(
            'name'       => __('Street / No.', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_street',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // Postcode
        $cmb->add_field(array(
            'name'       => __('Postcode', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_postcode',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // City
        $cmb->add_field(array(
            'name'       => __('City', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_city',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // Country
        $cmb->add_field(array(
            'name'       => __('Country', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_country',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // Initiate the metabox Information
        $cmb = new_cmb2_box(array(
            'id'           => CB_METABOX_PREFIX . 'location_info',
            'title'        => __('General Location information', 'commonsbooking'),
            'object_types' => array('cb_location',), // Post type
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            // 'closed'     => true, // Keep the metabox closed by default
        ));

        // short description
        $cmb->add_field(array(
            'name'       => __('Location email', 'commonsbooking'),
            'desc'       => __('email-address to get copy of booking confirmation and cancellation mails', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_email',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // short description
        $cmb->add_field(array(
            'name'       => __('Shortdescription', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_shortdescription',
            'type'       => 'text',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // pickup description
        $cmb->add_field(array(
            'name'       => __('Pickup instructions', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_pickupinstructions',
            'type'       => 'textarea_small',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // location contact
        $cmb->add_field(array(
            'name'       => __('Location contact information', 'commonsbooking'),
            'desc'       => __('field description (optional)', 'commonsbooking'),
            'id'         => CB_METABOX_PREFIX . 'location_contact',
            'type'       => 'textarea_small',
            'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
            // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
            // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
            // 'on_front'        => false, // Optionally designate a field to wp-admin only
            // 'repeatable'      => true,
        ));

        // Location admin selection
        $users = UserRepository::getCBLocationAdmins();
        $userOptions = [];
        foreach ($users as $user) {
            $userOptions[$user->ID] = $user->get('user_nicename') . " (" .$user->last_name . " " . $user->last_name . ")";
        }

        $cmb->add_field( array(
            'name'       => __('Location Admin(s)', 'commonsbooking'),
            'desc'       => __('Location Admin(s) field description (optional)', 'commonsbooking'),
            'id'      => CB_METABOX_PREFIX . 'location_admins',
            'type'    => 'pw_multiselect',
            'options' => $userOptions,
            'attributes' => array(
                'placeholder' => __('Select location admins.', 'commonsbooking')
            ),
        ) );
    }

}
