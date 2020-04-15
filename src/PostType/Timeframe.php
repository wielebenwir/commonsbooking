<?php

namespace CommonsBooking\PostType;

use CommonsBooking\Form\Field;
use CommonsBooking\Wordpress\MetaBox;

class Timeframe extends PostType
{

    const TYPE = 'cb_timeframe';

    protected $metaboxes;

    protected $menuPosition = 1;

    protected $listColumns = [
        'location-id' => "Location ID",
        'item-id' => "Item ID",
        'type' => "Type"
    ];

    /**
     * Item constructor.
     */
    public function __construct()
    {
        // Detail View
        /**
         * https://sltaylor.co.uk/blog/control-your-own-wordpress-custom-fields/
         */
        add_action( 'admin_menu', array( $this, 'createCustomFields' ) );
        add_action( 'do_meta_boxes', array( $this, 'removeDefaultCustomFields' ), 10, 3 );
        add_action( 'save_post', array( $this, 'saveCustomFields' ), 1, 2 );

        $this->initListView();
        $this->removeListTitleColumn();
        $this->removeListDateColumn();
    }

    public function getArgs()
    {
        $labels = array(
            'name'                  => __( 'Timeframes', TRANSLATION_CONST ),
            'singular_name'         => __( 'Timeframe', TRANSLATION_CONST ),
            'add_new'               => __( 'Hinzufügen', TRANSLATION_CONST ),
            'add_new_item'          => __( 'Timeframe hinzufügen', TRANSLATION_CONST ),
            'edit_item'             => __( 'Timeframe bearbeiten', TRANSLATION_CONST ),
            'new_item'              => __( 'Timeframe hinzufügen', TRANSLATION_CONST ),
            'view_item'             => __( 'Timeframe anzeigen', TRANSLATION_CONST ),
            'view_items'            => __( 'Timeframes anzeigen', TRANSLATION_CONST ),
            'search_items'          => __( 'Timeframe suchen', TRANSLATION_CONST ),
            'not_found'             => __( 'Keine Timeframes gefunden', TRANSLATION_CONST ),
            'not_found_in_trash'    => __( 'Keine Timeframes im Papierkorb gefunden', TRANSLATION_CONST ),
            'parent_item_colon'     => __( 'Übergeordnete Timeframes:', TRANSLATION_CONST ),
            'all_items'             => __( 'Alle Timeframes', TRANSLATION_CONST ),
            'archives'              => __( 'Timeframe Archiv', TRANSLATION_CONST ),
            'attributes'            => __( 'Timeframe Attribute', TRANSLATION_CONST ),
            'insert_into_item'      => __( 'Zum Timeframe hinzufügen', TRANSLATION_CONST ),
            'uploaded_to_this_item' => __( 'Zum Timeframe hinzugefügt', TRANSLATION_CONST ),
            'featured_image'        => __( 'Timeframebild', TRANSLATION_CONST ),
            'set_featured_image'    => __( 'Timeframebild setzen', TRANSLATION_CONST ),
            'remove_featured_image' => __( 'Timeframebild entfernen', TRANSLATION_CONST ),
            'use_featured_image'    => __( 'Als Timeframebild verwenden', TRANSLATION_CONST ),
            'menu_name'             => __( 'Timeframes', TRANSLATION_CONST ),
        );

        // args for the new post_type
        return array(
            'labels'              => $labels,

            // Sichtbarkeit des Post Types
            'public'              => false,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'             => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu' =>     false,

            // Position im Menu
            'menu_position'       => 2,

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
            'supports'            => array('custom-fields', 'revisions'),

            // Soll der Post Type Archiv-Seiten haben?
            'has_archive'         => false,

            // Soll man den Post Type exportieren können?
            'can_export'          => false,

            // Slug unseres Post Types für die redirects
            // dieser Wert wird später in der URL stehen
            'rewrite'             => array('slug' => self::TYPE),
        );
    }

    public function getPostType()
    {
        return self::TYPE;
    }

    protected function getCustomFields() {
        return array(
            new Field("location-id", "Location", "", "text", "edit_posts"),
            new Field("item-id", "Item", "", "text", "edit_posts"),
            new Field("start-date", "start-date", "", "text", "edit_posts"),
            new Field("end-date", "end-date", "",
                "text",
                "edit_pages"),
            new Field(
                "grid",
                "grid", "",
                "textarea",
                "edit_pages"
            ),
            new Field("type", "type", "",
                "text",
                "edit_pages"
            ),
            new Field("repetition", "repetition", "",
                "text",
                "edit_pages"
            ),
            new Field("weekdays", "weekdays", "",
                "text",
                "edit_pages"
            ),
            new Field("repetition-end", "repetition-end", "",
                "text",
                "edit_pages"
            ),
            new Field("user-id", "user-id", "",
                "text",
                "edit_pages"
            )
        );
    } 

    /**
     * @return array
     */
    public function getMetaboxes(): array
    {
        if($this->metaboxes == null) {
            $this->metaboxes[] = new MetaBox(self::TYPE . "-custom-fields", "Timeframe", array($this, 'renderMetabox'), self::TYPE );
        }
        return $this->metaboxes;
    }

}
