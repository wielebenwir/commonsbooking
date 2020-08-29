<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Plugin;
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
        'type'             => "Type",
        'location-id'      => "Location",
        'item-id'          => "Item",
        'repetition-start' => "Start Date",
        'repetition-end'   => "End date"
    ];

    /**
     * Timeframetypes which cannot be "overbooked"
     * @var int[]
     */
    public static $multiDayBlockingFrames = [
        self::REPAIR_ID,
        self::BOOKING_ID
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

        // Setting role permissions
        add_action('admin_init', array($this, 'addRoleCaps'), 999);
    }

    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            ob_start();
            global $post;
            cb_get_template_part('booking');
            $cb_content = ob_get_clean();
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

            $startDate = isset($_REQUEST['repetition-start']) && $_REQUEST['repetition-start'] != "" ? $_REQUEST['repetition-start'] : null;
            $endDate = isset($_REQUEST['repetition-end']) && $_REQUEST['repetition-end'] != "" ? $_REQUEST['repetition-end'] : null;

            /** @var \CommonsBooking\Model\Booking $booking */
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
                "post_title"  => __("Booking", 'commonsbooking')
            );

            if (empty($booking)) {
                $postarr['post_name'] = self::generateRandomSlug();
                $postId = wp_insert_post($postarr, true);
                $booking_metafield = new \CommonsBooking\Model\Booking($postId);
                // we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
                $booking_metafield->assignBookableTimeframeFields();
            } else {
                $postarr['ID'] = $booking->ID;
                $postId = wp_update_post($postarr);
                $booking_metafield = new \CommonsBooking\Model\Booking($postId);
                // we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe  
                $booking_metafield->assignBookableTimeframeFields();
            }

            // Trigger Mail, only send mail if status has changed     
            if ( ! empty($booking) and $booking->post_status != $_REQUEST["post_status"]) {
                $booking_msg = new \CommonsBooking\Messages\Messages($postId, $_REQUEST["post_status"]);
                $booking_msg->triggerMail();
            }
            // get slug as parameter
            $post_slug = get_post($postId)->post_name;


            wp_redirect(home_url('?' . self::getPostType() . '=' . $post_slug));
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
            'name'                  => __('Timeframes', 'commonsbooking'),
            'singular_name'         => __('Timeframe', 'commonsbooking'),
            'add_new'               => __('Add new', 'commonsbooking'),
            'add_new_item'          => __('Add new timeframe', 'commonsbooking'),
            'edit_item'             => __('Edit timeframe', 'commonsbooking'),
            'new_item'              => __('Add new timeframe', 'commonsbooking'),
            'view_item'             => __('Show timeframe', 'commonsbooking'),
            'view_items'            => __('Show timeframes', 'commonsbooking'),
            'search_items'          => __('Search timeframes', 'commonsbooking'),
            'not_found'             => __('Timeframes not found', 'commonsbooking'),
            'not_found_in_trash'    => __('No timeframes found in trash', 'commonsbooking'),
            'parent_item_colon'     => __('Parent timeframes:', 'commonsbooking'),
            'all_items'             => __('All timeframes', 'commonsbooking'),
            'archives'              => __('Timeframe archive', 'commonsbooking'),
            'attributes'            => __('Timeframe attributes', 'commonsbooking'),
            'insert_into_item'      => __('Add to timeframe', 'commonsbooking'),
            'uploaded_to_this_item' => __('Added to timeframe', 'commonsbooking'),
            'featured_image'        => __('Timeframe image', 'commonsbooking'),
            'set_featured_image'    => __('set timeframe image', 'commonsbooking'),
            'remove_featured_image' => __('remove timeframe image', 'commonsbooking'),
            'use_featured_image'    => __('use as timeframe image', 'commonsbooking'),
            'menu_name'             => __('Timeframes', 'commonsbooking'),
        );

        // args for the new post_type
        return array(
            'labels'            => $labels,

            // Sichtbarkeit des Post Types
            'public'            => false,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'           => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'      => false,

            // Position im Menu
            'menu_position'     => 2,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar' => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus' => true,

            // Hier können Berechtigungen in einem Array gesetzt werden
            // oder die standart Werte post und page in form eines Strings gesetzt werden
            'capability_type'   => array(self::$postType, self::$postType . 's'),

            'map_meta_cap'        => true,

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
            new Field("comment", __("Comment", 'commonsbooking'), "", "textarea_small", "edit_posts"),
            new Field("type", __('Type', 'commonsbooking'), "", "select", "edit_pages",
                self::getTypes()
            ),
            new Field("location-id", __("Location", 'commonsbooking'), "", "select", "edit_posts",
                \CommonsBooking\Repository\Location::getByCurrentUser(), true),
            new Field("item-id", __("Item", 'commonsbooking'), "", "select", "edit_posts",
                \CommonsBooking\Repository\Item::getByCurrentUser(), true),
            new Field("title-timeframe-config", __("Configure timeframe", 'commonsbooking'), "", "title", "edit_posts"),
            new Field("timeframe-repetition", __('Timeframe Repetition', 'commonsbooking'), "", "select", "edit_pages",
                [
                    'norep' => __("No Repetition", 'commonsbooking'),
                    'd'     => __("Daily", 'commonsbooking'),
                    'w'     => __("Weekly", 'commonsbooking'),
                    'm'     => __("Monthly", 'commonsbooking'),
                    'y'     => __("Yearly", 'commonsbooking')
                ]
            ),
            new Field("full-day", __('Full day', 'commonsbooking'), "", "checkbox", "edit_pages"),
            new Field("start-time", __("Start time", 'commonsbooking'), "", "text_time", "edit_posts"),
            new Field("end-time", __("End time", 'commonsbooking'), "", "text_time", "edit_pages"),
            new Field("grid", __("Grid", 'commonsbooking'), "", "select", "edit_pages",
                [
                    0 => __("Full slot", 'commonsbooking'),
                    1 => __("Hourly", 'commonsbooking')
                ]
            ),
            new Field("title-timeframe-rep-config", __("Configure repetition", 'commonsbooking'), "", "title",
                "edit_posts"),
            new Field("repetition-start", __('Repetition start', 'commonsbooking'), "", "text_date_timestamp",
                "edit_pages"),
            new Field("weekdays", __('Weekdays', 'commonsbooking'), "", "multicheck", "edit_pages",
                [
                    1 => __("Monday", 'commonsbooking'),
                    2 => __("Tuesday", 'commonsbooking'),
                    3 => __("Wednesday", 'commonsbooking'),
                    4 => __("Thursday", 'commonsbooking'),
                    5 => __("Friday", 'commonsbooking'),
                    6 => __("Saturday", 'commonsbooking'),
                    7 => __("Sunday", 'commonsbooking')
                ]
            ),
            new Field("repetition-end", __('Repetition end', 'commonsbooking'), "", "text_date_timestamp", "edit_pages")
        );
    }

    /**
     * Returns timeframe types.
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::OPENING_HOURS_ID    => __("Opening Hours", 'commonsbooking'),
            // disabled as its not implemented yet
            self::BOOKABLE_ID         => __("Bookable", 'commonsbooking'),
            self::HOLIDAYS_ID         => __("Holidays", 'commonsbooking'),
            self::OFF_HOLIDAYS_ID     => __("Official Holiday", 'commonsbooking'),
            // disabled as its not implemented yet
            self::REPAIR_ID           => __("Repair", 'commonsbooking'),
            self::BOOKING_ID          => __("Booking", 'commonsbooking'),
            self::BOOKING_CANCELED_ID => __("Booking cancelled", 'commonsbooking')
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
                case 'repetition-start':
                case 'repetition-end':
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
     * 1 => __("Opening Hours", 'commonsbooking'),
     * 2 => __("Bookable", 'commonsbooking'),
     * 3 => __("Holidays", 'commonsbooking'),
     * 4 => __("Official Holiday", 'commonsbooking'),
     * 5 => __("Repair", 'commonsbooking'),
     * 6 => __("Booking", 'commonsbooking'),
     * 7 => __("Booking cancelled", 'commonsbooking')
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
     * Returns true if frame is overbookable.
     * @param \WP_Post $timeframe
     *
     * @return bool
     */
    public static function isOverBookable(\WP_Post $timeframe)
    {
        return ! in_array(get_post_meta($timeframe->ID, 'type', true), self::$multiDayBlockingFrames);
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
                <option value=""><?php _e('Filter By Type ', 'commonsbooking'); ?></option>
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
