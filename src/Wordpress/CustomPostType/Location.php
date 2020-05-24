<?php

namespace CommonsBooking\Wordpress\CustomPostType;

class Location extends CustomPostType
{

    public static $postType = 'cb_location';

    /**
     * Item constructor.
     */
    public function __construct()
    {
        add_filter( 'the_content', array( $this, 'getTemplate' ) );
    }

    public function getTemplate( $content ) {

        $cb_content = '';
        if ( is_singular ( self::getPostType()  ) ) {
            $cb_content = cb_get_template_part( 'calendar', 'location' );
        } // if archive...

        return $content . $cb_content;

    }

    public function getArgs()
    {
        $labels = array(
            'name'                  => __( 'Locations', CB_TEXTDOMAIN ),
            'singular_name'         => __( 'Location', CB_TEXTDOMAIN ),
            'add_new'               => __( 'Hinzufügen', CB_TEXTDOMAIN ),
            'add_new_item'          => __( 'Location hinzufügen', CB_TEXTDOMAIN ),
            'edit_item'             => __( 'Location bearbeiten', CB_TEXTDOMAIN ),
            'new_item'              => __( 'Location hinzufügen', CB_TEXTDOMAIN ),
            'view_item'             => __( 'Location anzeigen', CB_TEXTDOMAIN ),
            'view_items'            => __( 'Locations anzeigen', CB_TEXTDOMAIN ),
            'search_items'          => __( 'Location suchen', CB_TEXTDOMAIN ),
            'not_found'             => __( 'Keine Locations gefunden', CB_TEXTDOMAIN ),
            'not_found_in_trash'    => __( 'Keine Locations im Papierkorb gefunden', CB_TEXTDOMAIN ),
            'parent_item_colon'     => __( 'Übergeordnete Locations:', CB_TEXTDOMAIN ),
            'all_items'             => __( 'Alle Locations', CB_TEXTDOMAIN ),
            'archives'              => __( 'Location Archiv', CB_TEXTDOMAIN ),
            'attributes'            => __( 'Location Attribute', CB_TEXTDOMAIN ),
            'insert_into_item'      => __( 'Zum Location hinzufügen', CB_TEXTDOMAIN ),
            'uploaded_to_this_item' => __( 'Zum Location hinzugefügt', CB_TEXTDOMAIN ),
            'featured_image'        => __( 'Locationbild', CB_TEXTDOMAIN ),
            'set_featured_image'    => __( 'Locationbild setzen', CB_TEXTDOMAIN ),
            'remove_featured_image' => __( 'Locationbild entfernen', CB_TEXTDOMAIN ),
            'use_featured_image'    => __( 'Als Locationbild verwenden', CB_TEXTDOMAIN ),
            'menu_name'             => __( 'Locations', CB_TEXTDOMAIN ),
        );

        // args for the new post_type
        return array(
            'labels'              => $labels,

            // Sichtbarkeit des Post Types
            'public'              => true,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'             => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu' =>     false,

            // Position im Menu
            'menu_position'       => 4,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar'   => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus'   => true,

            // Hier können Berechtigungen in einem Array gesetzt werden
            // oder die standart Werte post und page in form eines Strings gesetzt werden
            'capability_type'     => 'post',

            // Soll es im Frontend abrufbar sein?
            'publicly_queryable'  => true,

            // Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
            'exclude_from_search' => true,

            // Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
            'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions'),

            // Soll der Post Type Archiv-Seiten haben?
            'has_archive'         => false,

            // Soll man den Post Type exportieren können?
            'can_export'          => false,

            // Slug unseres Post Types für die redirects
            // dieser Wert wird später in der URL stehen
            'rewrite'             => array('slug' => self::getPostType()),
        );
    }

    public static function getView() {
        return new \CommonsBooking\View\Location();
    }

}
