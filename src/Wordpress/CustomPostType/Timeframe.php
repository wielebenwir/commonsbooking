<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Form\Field;
use CommonsBooking\Wordpress\MetaBox;

class Timeframe extends CustomPostType
{

    const OPENING_HOURS_ID = 1;
    const BOOKABLE_ID = 2;
    const HOLIDAYS_ID = 3;
    const OFF_HOLIDAYS_ID = 4;
    const REPAIR_ID = 5;
    const BOOKING_ID = 6;
    const BOOKING_CANCELED_ID = 7;

    public static $postType = 'cb_timeframe';

    protected $metaboxes;

    protected $menuPosition = 1;

    protected $types;

    protected $listColumns = [
        'type' => "Type",
        'location-id' => "Location",
        'item-id' => "Item",
        'start-date' => "Start Date",
        'end-date' => "End date"
    ];

    public static $multiDayFrames = [
        self::BOOKING_ID,
        self::BOOKING_CANCELED_ID
    ];

    /**
     * Item constructor.
     */
    public function __construct()
    {
        $this->types = self::getTypes();

        // Detail View
        /**
         * https://sltaylor.co.uk/blog/control-your-own-wordpress-custom-fields/
         */
        add_action( 'admin_menu', array( $this, 'createCustomFields' ) );
        add_action( 'do_meta_boxes', array( $this, 'removeDefaultCustomFields' ), 10, 3 );
        add_action( 'save_post', array( $this, 'saveCustomFields' ), 1, 2 );

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
            'rewrite'             => array('slug' => self::getPostType()),
        );
    }

    /**
     * @return array
     */
    protected function getCustomFields() {
        return array(
            new Field("location-id", __("Location", TRANSLATION_CONST), "", "selectbox", "edit_posts", Location::getAllPosts()),
            new Field("item-id",  __("Item", TRANSLATION_CONST), "", "selectbox", "edit_posts", Item::getAllPosts()),
            new Field("start-date",  __("Start date", TRANSLATION_CONST), "", "datetime", "edit_posts"),
            new Field("end-date",  __("End date", TRANSLATION_CONST), "", "datetime", "edit_pages"),
            new Field("grid", __("Grid", TRANSLATION_CONST), "", "selectbox", "edit_pages",
                [
                    1, 2, 3, 4
                ]
            ),
            new Field("type", __( 'Type', TRANSLATION_CONST ), "", "selectbox", "edit_pages",
                self::getTypes()
            ),
            new Field("repetition", __( 'Repetition', TRANSLATION_CONST ), "", "selectbox", "edit_pages",
                [
                    'd' => __("Daily", TRANSLATION_CONST),
                    'w' => __("Weekly", TRANSLATION_CONST),
                    'm' => __("Monthly", TRANSLATION_CONST),
                    'y' => __("Yearly", TRANSLATION_CONST)
                ]
            ),
            new Field("weekdays", __( 'Weekdays', TRANSLATION_CONST ), "","checkboxes","edit_pages",
                [
                    1 => __("Monday", TRANSLATION_CONST),
                    2 => __("Tuesday", TRANSLATION_CONST),
                    3 => __("Wednesday", TRANSLATION_CONST),
                    4 => __("Thursday", TRANSLATION_CONST),
                    5 => __("Friday", TRANSLATION_CONST),
                    6 => __("Saturday", TRANSLATION_CONST),
                    7 => __("Sunday", TRANSLATION_CONST)
                ]
            ),
            new Field("repetition-end", __( 'Repetition end', TRANSLATION_CONST ), "","date","edit_pages")
        );
    }

    /**
     * Returns timeframe types.
     * @return array
     */
    public static function getTypes() {
        return [
            self::OPENING_HOURS_ID => __("Opening Hours", TRANSLATION_CONST),
            self::BOOKABLE_ID => __("Bookable", TRANSLATION_CONST),
            self::HOLIDAYS_ID => __("Holidays", TRANSLATION_CONST),
            self::OFF_HOLIDAYS_ID => __("Official Holiday", TRANSLATION_CONST),
            self::REPAIR_ID => __("Repair", TRANSLATION_CONST),
            self::BOOKING_ID => __("Booking", TRANSLATION_CONST),
            self::BOOKING_CANCELED_ID => __("Booking cancelled", TRANSLATION_CONST)
        ];
    }

    /**
     * Returns type label by type-id.
     * @param $id
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getTypeLabel($id) {
        if(array_key_exists($id, self::getTypes())) {
            return self::getTypes()[$id];
        } else {
            throw new \Exception('invalid type id');
        }
    }

    /**
     * Returns metaboxes for timeframe.
     * @return array
     */
    public function getMetaboxes(): array
    {
        if($this->metaboxes == null) {
            $this->metaboxes[] = new MetaBox(self::getPostType() . "-custom-fields", "Timeframe", array($this, 'renderMetabox'), self::getPostType() );
        }
        return $this->metaboxes;
    }

    /**
     * Adds data to custom columns
     * @param $column
     * @param $post_id
     */
    public function setCustomColumnsData($column, $post_id) {
        if($value = get_post_meta( $post_id , $column , true )) {
            switch($column) {
                case 'location-id':
                case 'item-id':
                    if( $post = get_post($value)) {
                        if (get_post_type($post) == Location::getPostType() || get_post_type($post) == Item::getPostType()) {
                            echo $post->post_title;
                            break;
                        }
                    }
                    echo '-';
                    break;
                case 'type':
                    $typeField = null;
                    $output = "-";
                    /** @var Field $customField */
                    foreach ($this->getCustomFields() as $customField) {
                        if($customField->getName() == 'type') {
                            foreach ($customField->getOptions() as $key => $label) {
                                if($value == $key) {
                                    $output = $label;
                                }
                            }
                        }
                    }
                    echo $output;
                    break;
                default:
                    echo $value;
                    break;
            }
        }
    }

    /**
     * Priorities:
        1 => __("Opening Hours", TRANSLATION_CONST),
        2 => __("Bookable", TRANSLATION_CONST),
        3 => __("Holidays", TRANSLATION_CONST),
        4 => __("Official Holiday", TRANSLATION_CONST),
        5 => __("Repair", TRANSLATION_CONST),
        6 => __("Booking", TRANSLATION_CONST),
        7 => __("Booking cancelled", TRANSLATION_CONST)
     * @param \WP_Post $timeframeOne
     * @param \WP_Post $timeframeTwo
     */
    public static function getHigherPrioFrame(\WP_Post $timeframeOne, \WP_Post $timeframeTwo) {
        $typeOne = get_post_meta($timeframeOne->ID, 'type', true);
        $typeTwo = get_post_meta($timeframeTwo->ID, 'type', true);
        //@TODO: Define visualisation.
    }

    /**
     * Returns view-class.
     * @return \CommonsBooking\View\Timeframe
     */
    public static function getView() {
        return new \CommonsBooking\View\Timeframe();
    }

}
