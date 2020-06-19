<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Repository\Booking;
use CommonsBooking\Wordpress\MetaBox\Field;
use CommonsBooking\Messages\Messages;

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

    protected $menuPosition = 1;

    protected $types;

    protected $listColumns = [
        'type'        => "Type",
        'location-id' => "Location",
        'item-id'     => "Item",
        'start-date'  => "Start Date",
        'end-date'    => "End date"
    ];

    public static $multiDayFrames = [
        self::BOOKING_ID,
        self::BOOKING_CANCELED_ID,
        self::REPAIR_ID
    ];

    /**
     * Item constructor.
     */
    public function __construct()
    {
        $this->types = self::getTypes();

        // Set Tepmlates
        add_filter('the_content', array($this, 'getTemplate'));

        // Add Meta Boxes
        add_action('cmb2_admin_init', array($this, 'registerMetabox'));

        // Remove not needed Meta Boxes
        add_action('do_meta_boxes', array($this, 'removeDefaultCustomFields'), 10, 3);

        // List settings
        $this->removeListDateColumn();

        add_action('save_post', array($this, 'saveCustomFields'), 1, 2);

        // Save-handling
        $this->handleFormRequest();

        // Add type filter to backend list view
        add_action('restrict_manage_posts', array(self::class, 'addAdminTypeFilter'));
        add_filter('parse_query', array($this, 'filterAdminList'));
    }

    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            global $post;
            $cb_content = cb_get_template_part('booking', $post->post_status);
        } // if archive...

        return $cb_content . $content;
    }

    /**
     * Handles save-Request for timeframe.
     */
    public function handleFormRequest()
    {
        if (
            isset($_REQUEST[static::getWPNonceId()]) &&
            wp_verify_nonce($_REQUEST[static::getWPNonceId()], static::getWPAction())
        ) {
            $itemId = isset($_REQUEST['item-id']) && $_REQUEST['item-id'] != "" ? $_REQUEST['item-id'] : null;
            $locationId = isset($_REQUEST['location-id']) && $_REQUEST['location-id'] != "" ? $_REQUEST['location-id'] : null;

            if ( ! get_post($itemId)) {
                throw new \Exception('Item does not exist. (' . $itemId . ')');
            }
            if ( ! get_post($locationId)) {
                throw new \Exception('Location does not exist. (' . $locationId . ')');
            }

            $startDate = isset($_REQUEST['start-date']) && $_REQUEST['start-date'] != "" ? $_REQUEST['start-date'] : null;
            $endDate = isset($_REQUEST['end-date']) && $_REQUEST['end-date'] != "" ? $_REQUEST['end-date'] : null;

            /** @var \WP_Post $booking */
            $booking = Booking::getBookingByDate(
                $startDate,
                $endDate,
                $locationId,
                $itemId
            );


            $postarr = array(
                "type"        => $_REQUEST["type"],
                "post_status" => $_REQUEST["post_status"],
                "post_type"   => self::getPostType(),
                "post_title"  => __("Buchung", CB_TEXTDOMAIN)
            );

            if (empty($booking)) {
                $postarr['post_name'] = self::generateRandomSlug();
                $postId = wp_insert_post($postarr, true);
                /**
                 ********************** TEST MAIL ****************
                 */         
                //\CommonsBooking\Messages\Messages::SendNotificationMail('','','');
            } else {
                $postarr['ID'] = $booking->ID;
                $postId = wp_update_post($postarr);;
               
                /**
                 ********************** TEST MAIL ****************
                 */    
                //\CommonsBooking\Messages\Messages::SendNotificationMail('','','');
                
            }

            // Trigger Mail
            $booking_msg = new \CommonsBooking\Messages\Messages($postId, $_REQUEST["post_status"]);
            $booking_msg->triggerMail();
    

            // Generate random post slug
            $post_slug = get_post($postId)->post_name;

            //wp_redirect(home_url('?' . self::getPostType() . '=' . $post_slug));
            exit;
        }
    }

    /**
     * Returns CPT arguments.
     * @return array
     */
    public function getArgs()
    {
        $labels = array(
            'name'                  => __('Timeframes', CB_TEXTDOMAIN),
            'singular_name'         => __('Timeframe', CB_TEXTDOMAIN),
            'add_new'               => __('Hinzufügen', CB_TEXTDOMAIN),
            'add_new_item'          => __('Timeframe hinzufügen', CB_TEXTDOMAIN),
            'edit_item'             => __('Timeframe bearbeiten', CB_TEXTDOMAIN),
            'new_item'              => __('Timeframe hinzufügen', CB_TEXTDOMAIN),
            'view_item'             => __('Timeframe anzeigen', CB_TEXTDOMAIN),
            'view_items'            => __('Timeframes anzeigen', CB_TEXTDOMAIN),
            'search_items'          => __('Timeframe suchen', CB_TEXTDOMAIN),
            'not_found'             => __('Keine Timeframes gefunden', CB_TEXTDOMAIN),
            'not_found_in_trash'    => __('Keine Timeframes im Papierkorb gefunden', CB_TEXTDOMAIN),
            'parent_item_colon'     => __('Übergeordnete Timeframes:', CB_TEXTDOMAIN),
            'all_items'             => __('Alle Timeframes', CB_TEXTDOMAIN),
            'archives'              => __('Timeframe Archiv', CB_TEXTDOMAIN),
            'attributes'            => __('Timeframe Attribute', CB_TEXTDOMAIN),
            'insert_into_item'      => __('Zum Timeframe hinzufügen', CB_TEXTDOMAIN),
            'uploaded_to_this_item' => __('Zum Timeframe hinzugefügt', CB_TEXTDOMAIN),
            'featured_image'        => __('Timeframebild', CB_TEXTDOMAIN),
            'set_featured_image'    => __('Timeframebild setzen', CB_TEXTDOMAIN),
            'remove_featured_image' => __('Timeframebild entfernen', CB_TEXTDOMAIN),
            'use_featured_image'    => __('Als Timeframebild verwenden', CB_TEXTDOMAIN),
            'menu_name'             => __('Timeframes', CB_TEXTDOMAIN),
        );

        // args for the new post_type
        return array(
            'labels'              => $labels,

            // Sichtbarkeit des Post Types
            'public'              => false,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'             => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'        => false,

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
            'supports'            => array('title', 'author', 'custom-fields', 'revisions'),

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
     * Returns custom (meta) fields for CPT.
     * @return array
     */
    protected function getCustomFields()
    {
        return array(
            new Field("comment", __("Comment", CB_TEXTDOMAIN), "", "textarea_small", "edit_posts"),
            new Field("type", __('Type', CB_TEXTDOMAIN), "", "select", "edit_pages",
                self::getTypes()
            ),
            new Field("location-id", __("Location", CB_TEXTDOMAIN), "", "select", "edit_posts", Location::getAllPosts()),
            new Field("item-id", __("Item", CB_TEXTDOMAIN), "", "select", "edit_posts", Item::getAllPosts()),
            new Field("title-timeframe-config", __("Configure timeframe", CB_TEXTDOMAIN), "", "title", "edit_posts"),
            new Field("timeframe-repetition", __('Timeframe Repetition', CB_TEXTDOMAIN), "", "select", "edit_pages",
                [
                    #  'rep' => __("Repetition", CB_TEXTDOMAIN),
                    'norep' => __("No Repetition", CB_TEXTDOMAIN),
                    'd'     => __("Daily", CB_TEXTDOMAIN),
                    'w'     => __("Weekly", CB_TEXTDOMAIN),
                    'm'     => __("Monthly", CB_TEXTDOMAIN),
                    'y'     => __("Yearly", CB_TEXTDOMAIN)
                ]
            ),
            new Field("full-day", __('Full day', CB_TEXTDOMAIN), "", "checkbox", "edit_pages"),
            new Field("start-date", __("Start date", CB_TEXTDOMAIN), "", "text_datetime_timestamp", "edit_posts"),
            new Field("end-date", __("End date", CB_TEXTDOMAIN), "", "text_datetime_timestamp", "edit_pages"),
            new Field("start-time", __("Start time", CB_TEXTDOMAIN), "", "text_time", "edit_posts"),
            new Field("end-time", __("End time", CB_TEXTDOMAIN), "", "text_time", "edit_pages"),
            new Field("grid", __("Grid", CB_TEXTDOMAIN), "", "select", "edit_pages",
                [
                    0 => __("Full slot", CB_TEXTDOMAIN),
                    1 => __("Hourly", CB_TEXTDOMAIN)
                ]
            ),
            new Field("title-timeframe-rep-config", __("Configure repetition", CB_TEXTDOMAIN), "", "title", "edit_posts"),
            new Field("repetition-start", __('Repetition start', CB_TEXTDOMAIN), "", "text_date", "edit_pages"),
            /*new Field("repetition", __('Repetition', CB_TEXTDOMAIN), "", "select", "edit_pages",
                [
                    'd' => __("Daily", CB_TEXTDOMAIN),
                    'w' => __("Weekly", CB_TEXTDOMAIN),
                    'm' => __("Monthly", CB_TEXTDOMAIN),
                    'y' => __("Yearly", CB_TEXTDOMAIN)
                ]
            ),*/
            new Field("weekdays", __('Weekdays', CB_TEXTDOMAIN), "", "multicheck", "edit_pages",
                [
                    1 => __("Monday", CB_TEXTDOMAIN),
                    2 => __("Tuesday", CB_TEXTDOMAIN),
                    3 => __("Wednesday", CB_TEXTDOMAIN),
                    4 => __("Thursday", CB_TEXTDOMAIN),
                    5 => __("Friday", CB_TEXTDOMAIN),
                    6 => __("Saturday", CB_TEXTDOMAIN),
                    7 => __("Sunday", CB_TEXTDOMAIN)
                ]
            ),
            new Field("repetition-end", __('Repetition end', CB_TEXTDOMAIN), "", "text_date", "edit_pages")
        );
    }

    /**
     * Returns timeframe types.
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::OPENING_HOURS_ID    => __("Opening Hours", CB_TEXTDOMAIN),
            self::BOOKABLE_ID         => __("Bookable", CB_TEXTDOMAIN),
            self::HOLIDAYS_ID         => __("Holidays", CB_TEXTDOMAIN),
            self::OFF_HOLIDAYS_ID     => __("Official Holiday", CB_TEXTDOMAIN),
            self::REPAIR_ID           => __("Repair", CB_TEXTDOMAIN),
            self::BOOKING_ID          => __("Booking", CB_TEXTDOMAIN),
            self::BOOKING_CANCELED_ID => __("Booking cancelled", CB_TEXTDOMAIN)
        ];
    }

    /**
     * Returns type label by type-id.
     *
     * @param $id
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getTypeLabel($id)
    {
        if (array_key_exists($id, self::getTypes())) {
            return self::getTypes()[$id];
        } else {
            throw new \Exception('invalid type id');
        }
    }

    /**
     * Adds data to custom columns
     *
     * @param $column
     * @param $post_id
     */
    public function setCustomColumnsData($column, $post_id)
    {
        if ($value = get_post_meta($post_id, $column, true)) {
            switch ($column) {
                case 'location-id':
                case 'item-id':
                    if ($post = get_post($value)) {
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
                        if ($customField->getName() == 'type') {
                            foreach ($customField->getOptions() as $key => $label) {
                                if ($value == $key) {
                                    $output = $label;
                                }
                            }
                        }
                    }
                    echo $output;
                    break;
                case 'start-date':
                case 'end-date':
                    echo date('d.m.Y H:i', $value);
                    break;
                default:
                    echo $value;
                    break;
            }
        }
    }

    /**
     * Priorities:
     * 1 => __("Opening Hours", CB_TEXTDOMAIN),
     * 2 => __("Bookable", CB_TEXTDOMAIN),
     * 3 => __("Holidays", CB_TEXTDOMAIN),
     * 4 => __("Official Holiday", CB_TEXTDOMAIN),
     * 5 => __("Repair", CB_TEXTDOMAIN),
     * 6 => __("Booking", CB_TEXTDOMAIN),
     * 7 => __("Booking cancelled", CB_TEXTDOMAIN)
     *
     * @param \WP_Post $timeframeOne
     * @param \WP_Post $timeframeTwo
     *
     * @return \WP_Post
     */
    public static function getHigherPrioFrame(\WP_Post $timeframeOne, \WP_Post $timeframeTwo)
    {

        $prioMapping = [
            self::REPAIR_ID        => 10,
            self::BOOKING_ID       => 9,
            self::HOLIDAYS_ID      => 8,
            self::OFF_HOLIDAYS_ID  => 7,
            self::BOOKABLE_ID      => 6,
            self::OPENING_HOURS_ID => 5
        ];

        $typeOne = get_post_meta($timeframeOne->ID, 'type', true);
        $typeTwo = get_post_meta($timeframeTwo->ID, 'type', true);

        return $prioMapping[$typeOne] > $prioMapping[$typeTwo] ? $timeframeOne : $timeframeTwo;
    }

    /**
     * Checks if timeframe is locked, so that an item cannot get booked.
     *
     * @param \WP_Post $timeframe
     *
     * @return bool
     */
    public static function isLocked(\WP_Post $timeframe)
    {
        $lockedTypes = [
            self::REPAIR_ID,
            self::HOLIDAYS_ID,
            self::OFF_HOLIDAYS_ID,
            self::BOOKING_ID
        ];

        return in_array(get_post_meta($timeframe->ID, 'type', true), $lockedTypes);
    }

    /**
     * Returns view-class.
     * @return \CommonsBooking\View\Timeframe
     */
    public static function getView()
    {
        return new \CommonsBooking\View\Timeframe();
    }

    /**
     * First create the dropdown
     * make sure to change POST_TYPE to the name of your custom post type
     *
     * @return void
     */
    public static function addAdminTypeFilter()
    {
        //only add filter to post type you want
        if (isset($_GET['post_type']) && self::$postType == $_GET['post_type']) {
            $values = self::getTypes();
            ?>
                <select name="admin_filter_type">
                    <option value=""><?php _e('Filter By Type ', CB_TEXTDOMAIN); ?></option>
                    <?php
                        $filterValue = isset($_GET['admin_filter_type']) ? $_GET['admin_' . self::$postType . '_filter_type'] : '';
                        foreach ($values as $value => $label) {
                            printf
                            (
                                '<option value="%s"%s>%s</option>',
                                $value,
                                $value == $filterValue ? ' selected="selected"' : '',
                                $label
                            );
                        }
                    ?>
                </select>
            <?php
        }
    }


    /**
     * if submitted filter by post meta
     *
     * @param  (wp_query object) $query
     *
     * @return Void
     */
    public static function filterAdminList($query)
    {
        global $pagenow;
        if (
            is_admin() &&
            isset($_GET['post_type']) && self::$postType == $_GET['post_type'] &&
            isset($_GET['admin_filter_type']) &&
            $pagenow == 'edit.php' &&
            $_GET['admin_filter_type'] != ''
        ) {
            $query->query_vars['meta_key'] = 'type';
            $query->query_vars['meta_value'] = $_GET['admin_filter_type'];
        }
    }


}
