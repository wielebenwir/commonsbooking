<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Repository\Booking;
use CommonsBooking\Repository\BookingCodes;

class Timeframe extends CustomPostType
{

    /**
     * "Opening Hours" timeframe type id.
     */
    const OPENING_HOURS_ID = 1;

    /**
     * "Bookable" timeframe type id.
     */
    const BOOKABLE_ID = 2;

    /**
     * "Holidays" timeframe type id.
     */
    const HOLIDAYS_ID = 3;

    /**
     * "Official Holidays" timeframe type id.
     */
    const OFF_HOLIDAYS_ID = 4;

    /**
     * "Repair" timeframe type id.
     */
    const REPAIR_ID = 5;

    /**
     * "Booking" timeframe type id.
     */
    const BOOKING_ID = 6;

    /**
     * "Booking cancelled" timeframe type id.
     */
    const BOOKING_CANCELED_ID = 7;

    /**
     * CPT type.
     * @var string
     */
    public static $postType = 'cb_timeframe';

    /**
     * Position in backend menu.
     * @var int
     */
    protected $menuPosition = 1;

    /**
     * @var array
     */
    protected $types;

    /**
     * Backend listing columns.
     * @var string[]
     */
    protected $listColumns = [
        'type'             => "Type",
        'item-id'          => "Item",
        'location-id'      => "Location",
        'post_date'        => "Buchungszeitpunkt",
        'repetition-start' => "Start Date",
        'repetition-end'   => "End date",
        'post_status'      => "Buchungs-Status"
    ];

    /**
     * Timeframetypes which cannot be "overbooked".
     * @var int[]
     */
    public static $multiDayBlockingFrames = [
        self::REPAIR_ID,
        self::BOOKING_ID
    ];

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
        add_action('restrict_manage_posts', array(self::class, 'addAdminItemFilter'));
        add_action('restrict_manage_posts', array(self::class, 'addAdminLocationFilter'));
        add_action('restrict_manage_posts', array(self::class, 'addAdminStatusFilter'));
        add_action('restrict_manage_posts', array(self::class, 'addAdminDateFilter'));
        add_action('pre_get_posts', array($this, 'filterAdminList'));

        // Setting role permissions
        add_action('admin_init', array($this, 'addRoleCaps'), 999);
    }

    /**
     * Registers metaboxes for cpt.
     */
    public function registerMetabox()
    {
        $cmb = new_cmb2_box([
            'id'           => static::getPostType() . "-custom-fields",
            'title'        => "Timeframe",
            'object_types' => array(static::getPostType())
        ]);


        foreach ($this->getCustomFields() as $customField) {
            $cmb->add_field($customField);
        }
    }

    /**
     * Save the new Custom Fields values
     */
    public function saveCustomFields($post_id, $post)
    {
        if ($post->post_type !== static::getPostType()) {
            return;
        }

        $noDeleteMetaFields = ['start-time', 'end-time', 'timeframe-repetition'];

        foreach ($this->getCustomFields() as $customField) {

            //@TODO: Find better solution for capability check for bookings
            if (
                (array_key_exists('type', $_REQUEST) && $_REQUEST['type'] == Timeframe::BOOKING_ID) ||
                current_user_can('edit_post', $post_id)
            ) {
                $fieldNames = [];
                if ($customField['type'] == "checkboxes") {
                    foreach ($customField['options'] as $key => $label) {
                        $fieldNames[] = $customField['id'] . "-" . $key;
                    }
                } else {
                    $fieldNames[] = $customField['id'];
                }

                foreach ($fieldNames as $fieldName) {
                    if ( ! array_key_exists($fieldName, $_REQUEST)) {
                        if ( ! in_array($fieldName, $noDeleteMetaFields)) {
                            delete_post_meta($post_id, $fieldName);
                        }
                        continue;
                    }

                    $value = $_REQUEST[$fieldName];
                    if (is_string($value)) {
                        $value = trim($value);
                        update_post_meta($post_id, $fieldName, $value);

                        // if we have a booking, there shall be set no repetition
                        if ($fieldName == "type" && $value == Timeframe::BOOKING_ID) {
                            update_post_meta($post_id, 'timeframe-repetition', 'norep');
                        }
                    }
                }
            }
        }

        // Validate timeframe
        $isValid = $this->validateTimeFrame($post_id, $post);

        if($isValid) {
            $timeframe = new \CommonsBooking\Model\Timeframe($post_id);
            if($timeframe->bookingCodesApplieable()) {
                BookingCodes::generate($post_id);
            }
        }
    }

    /**
     * Validates timeframe and sets state to draft if invalid.
     * @param $post_id
     * @param $post
     *
     * @throws \Exception
     */
    protected function validateTimeFrame($post_id, $post) {
        $timeframe = new \CommonsBooking\Model\Timeframe($post_id);
        if ( ! $timeframe->isValid()) {
            // set post_status to draft if not valid
            if ($post->post_status !== 'draft') {
                $post->post_status = 'draft';
                wp_update_post($post);
            }
            return false;
        }
        return true;
    }

    /**
     * @param $content
     *
     * @return string
     */
    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            ob_start();
            global $post;
            if (current_user_can('administrator') or get_current_user_id() == $post->post_author) {
                cb_get_template_part('booking', 'single');
            } else {
                cb_get_template_part('booking', 'single-notallowed');
            }
            $cb_content = ob_get_clean();
        } // if archive...

        return $content . $cb_content;
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

            // New booking
            if (empty($booking)) {
                $postarr['post_name'] = self::generateRandomSlug();
                $postId = wp_insert_post($postarr, true);
                $booking_metafield = new \CommonsBooking\Model\Booking($postId);
                // we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
                $booking_metafield->assignBookableTimeframeFields();
            // Existing booking
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

            wp_redirect(add_query_arg(self::getPostType(), $post_slug, home_url()));
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
     * Returns custom (meta) fields for Costum Post Type Timeframe.
     * @return array
     */
    protected function getCustomFields()
    {
        // We need static types, because german month names dont't work for datepicker
        $dateFormat = "d/m/Y";
        if(strpos(get_locale(), 'de_') !== false) {
            $dateFormat = "d.m.Y";
        }

        if(strpos(get_locale(), 'en_') !== false) {
            $dateFormat = "m/d/Y";
        }

        return array(
            array(
                'name'       => __("Comment", 'commonsbooking'),
                //'desc'       => __('', 'commonsbooking'),
                'id'         => "comment",
                'type'       => 'textarea_small'
            ),
            array(
                'name'       => __('Type', 'commonsbooking'),
                //'desc'       => __('', 'commonsbooking'),
                'id'         => "type",
                'type'       => 'select',
                'options'    => self::getTypes()
            ),
            array(
                'name'       => __('Maximum booking duration', 'commonsbooking'),
                'desc'       => __('Maximum booking duration in days', 'commonsbooking'),
                'id'         => "timeframe-max-days",
                'type'       => 'select',
                'options'    => [
                    '1'  => 1,
                    '2'  => 2,
                    '3'  => 3,
                    '4'  => 4,
                    '5'  => 5,
                    '6'  => 6,
                    '7'  => 7,
                    '8'  => 8,
                    '9'  => 9,
                    '10' => 10
                ],
                'default'    => 3
            ),
            array(
                'name'       => __("Location", 'commonsbooking'),
                'id'         => "location-id",
                'type'       => 'select',
                'options'    => self::sanitizeOptions(\CommonsBooking\Repository\Location::getByCurrentUser())
            ),
            array(
                'name'       => __("Item", 'commonsbooking'),
                'id'         => "item-id",
                'type'       => 'select',
                'options'    => self::sanitizeOptions(\CommonsBooking\Repository\Item::getByCurrentUser(), true)
            ),
            array(
                'name'       => __("Configure timeframe", 'commonsbooking'),
                'id'         => "title-timeframe-config",
                'type'       => 'title',
            ),
            array(
                'name'       => __('Timeframe Repetition', 'commonsbooking'),
                'id'         => "timeframe-repetition",
                'type'       => 'select',
                'options'    => [
                    'norep' => __("No Repetition", 'commonsbooking'),
                    'd'     => __("Daily", 'commonsbooking'),
                    'w'     => __("Weekly", 'commonsbooking'),
                    'm'     => __("Monthly", 'commonsbooking'),
                    'y'     => __("Yearly", 'commonsbooking')
                ]
            ),
            array(
                'name'       => __('Full day', 'commonsbooking'),
                'id'         => "full-day",
                'type'       => 'checkbox',
            ),
            array(
                'name'       => __('Maximum booking duration', 'commonsbooking'),
                'desc'       => __('Maximum booking duration in days', 'commonsbooking'),
                'id'         => "timeframe-max-days",
                'type'       => 'select',
                'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
                'options'    => [
                    '1'  => 1,
                    '2'  => 2,
                    '3'  => 3,
                    '4'  => 4,
                    '5'  => 5,
                    '6'  => 6,
                    '7'  => 7,
                    '8'  => 8,
                    '9'  => 9,
                    '10' => 10
                ],
                'default'    => 3
            ),
            array(
                'name'        => __("Start time", 'commonsbooking'),
                'id'          => "start-time",
                'type'        => 'text_time',
                'show_on_cb'  => 'cmb2_hide_if_no_cats', // function should return a bool value
                'attributes' => array(
                    'data-timepicker' => json_encode(
                        array(
                            'stepMinute' => 60
                        )
                    ),
                ),
                'time_format' => get_option('time_format'),
                'date_format' => $dateFormat
            ),
            array(
                'name'        => __("End time", 'commonsbooking'),
                'id'          => "end-time",
                'type'        => 'text_time',
                'attributes' => array(
                     'data-timepicker' => json_encode(
                        array(
                            'stepMinute' => 60
                        )
                     ),
                 ),
                'time_format' => get_option('time_format'),
                'date_format' => $dateFormat
            ),
            array(
                'name'       => __("Grid", 'commonsbooking'),
                'id'         => "grid",
                'type'       => 'select',
                'options'    => [
                    0 => __("Full slot", 'commonsbooking'),
                    1 => __("Hourly", 'commonsbooking')
                ]
            ),
            array(
                'name'       => __("Configure repetition", 'commonsbooking'),
                'id'         => "title-timeframe-rep-config",
                'type'       => 'title',
            ),
            array(
                'name'        => __('Repetition start', 'commonsbooking'),
                'id'          => "repetition-start",
                'type'        => 'text_date_timestamp',
                'time_format' => get_option('time_format'),
                'date_format' => $dateFormat
            ),
            array(
                'name'       => __('Weekdays', 'commonsbooking'),
                'id'         => "weekdays",
                'type'       => 'multicheck',
                'options'    => [
                    1 => __("Monday", 'commonsbooking'),
                    2 => __("Tuesday", 'commonsbooking'),
                    3 => __("Wednesday", 'commonsbooking'),
                    4 => __("Thursday", 'commonsbooking'),
                    5 => __("Friday", 'commonsbooking'),
                    6 => __("Saturday", 'commonsbooking'),
                    7 => __("Sunday", 'commonsbooking')
                ]
            ),
            array(
                'name'        => __('Repetition end', 'commonsbooking'),
                'id'          => "repetition-end",
                'type'        => 'text_date_timestamp',
                'time_format' => get_option('time_format'),
                'date_format' => $dateFormat
            ),
            array(
                'name'       => __('Create Booking Codes', 'commonsbooking'),
                'id'         => "create-booking-codes",
                'type'       => 'checkbox'
            ),
            array(
                'name' => __('Booking Codes', 'commonsbooking'),
                'id'   => 'booking-codes-list',
                'type' => 'title',
                'render_row_cb' => array(self::class, 'show_booking_code_checkbox'), // function should return a bool value
            ),
            array(
                'name' => __('Booking Code', 'commonsbooking'),
                'id'   => CB_METABOX_PREFIX . 'bookingcode',
                'type' => 'text',
                'show_on_cb' => array(self::class, 'show_booking_code'),
                'attributes' => array(
                    'disabled' => 'disabled',
                )
            )
        );
    }

    /**
     * Returns true, if timeframe is of type booking.
     * @param $field
     *
     * @return bool
     */
    public static function show_booking_code( $field ) {
        return get_post_meta( $field->object_id, 'type', true ) == self::BOOKING_ID;
    }

    /**
     * Callback function for booking code list.
     * @param $field_args
     * @param $field
     */
    public static function show_booking_code_checkbox( $field_args, $field ) {
        \CommonsBooking\View\BookingCodes::renderTable($field->object_id());
    }

    /**
     * Returns timeframe types.
     * @return array
     */
    public static function getTypes()
    {
        return [
            //self::OPENING_HOURS_ID    => __("Opening Hours", 'commonsbooking'),  // disabled as its not implemented yet
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

                    foreach ($this->getCustomFields() as $customField) {
                        if ($customField['id'] == 'type') {
                            foreach ($customField['options'] as $key => $label) {
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
        } else {
            $bookingColumns = [
                'post_date',
                'post_status'
            ];

            if (
                property_exists($post = get_post($post_id), $column) && (
                    ! in_array($column, $bookingColumns) ||
                    get_post_meta($post_id, 'type', true) == Timeframe::BOOKING_ID
                )
            ) {
                echo $post->{$column};
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
     *
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
     * Adds filter dropdown // filter by type (eg. bookable, repair etc.) in timeframe List
     *
     * @return void
     */
    public static function addAdminTypeFilter()
    {
        self::renderFilter(
            __('Filter By Type ', 'commonsbooking'),
            'filter_type',
            self::getTypes()
        );
    }

    /**
     * Adds filter dropdown // filter by item in timeframe List
     */
    public static function addAdminItemFilter()
    {
        $items = \CommonsBooking\Repository\Item::get(
            [
                'post_status' => 'any',
            ]
        );
        if ($items) {
            $values = [];
            foreach ($items as $item) {
                $values[$item->ID] = $item->post_title;
            }

            self::renderFilter(
                __('Filter By Item ', 'commonsbooking'),
                'filter_item',
                $values
            );
        }
    }

    /**
     * Adds filter dropdown // filter by location in timeframe List
     */
    public static function addAdminLocationFilter()
    {
        $locations = \CommonsBooking\Repository\Location::get(
            [
                'post_status' => 'any',
            ]
        );
        if ($locations) {
            $values = [];
            foreach ($locations as $location) {
                $values[$location->ID] = $location->post_title;
            }

            self::renderFilter(
                __('Filter By Location ', 'commonsbooking'),
                'filter_location',
                $values
            );
        }
    }

    /**
     * Adds filter dropdown // filter by location in timeframe List
     */
    public static function addAdminStatusFilter()
    {
        $values = [];
        foreach (\CommonsBooking\Model\Booking::$bookingStates as $bookingState) {
            $values[$bookingState] = $bookingState;
        }
        self::renderFilter(
            __('Filter By Status ', 'commonsbooking'),
            'filter_post_status',
            $values
        );
    }

    /**
     * Adds filter dropdown // filter by location in timeframe List
     */
    public static function addAdmindateFilter()
    {
        if (isset($_GET['post_type']) && self::$postType == $_GET['post_type']) {
            $startDateInputName = 'admin_filter_startdate';
            $endDateInputName = 'admin_filter_enddate';

            $from = ( isset( $_GET[$startDateInputName] ) && $_GET[$startDateInputName] ) ? $_GET[$startDateInputName] : '';
            $to = ( isset( $_GET[$endDateInputName] ) && $_GET[$endDateInputName] ) ? $_GET[$endDateInputName] : '';

            echo '<style>
                input[name=' . $startDateInputName . '], 
                input[name=' . $endDateInputName . ']{
                    line-height: 28px;
                    height: 28px;
                    margin: 0;
                    width:150px;
                }
            </style>
     
            <input type="text" name="' . $startDateInputName . '" placeholder="'. __('Repetition start', 'commonsbooking') .'" value="' . esc_attr( $from ) . '" />
            <input type="text" name="' . $endDateInputName . '" placeholder="' . __('Repetition end', 'commonsbooking') . '" value="' . esc_attr( $to ) . '" />
     
            <script>
            jQuery( function($) {
                var from = $(\'input[name=' . $startDateInputName . ']\'),
                    to = $(\'input[name=' . $endDateInputName . ']\');
     
                $(\'input[name=' . $startDateInputName . '], input[name=' . $endDateInputName . ']\' ).datepicker( 
                    {
                        dateFormat : "yy-mm-dd"
                    }
                );
                from.on( \'change\', function() {
                    to.datepicker( \'option\', \'minDate\', from.val() );
                }); 
                to.on( \'change\', function() {
                    from.datepicker( \'option\', \'maxDate\', to.val() );
                }); 
            });
            </script>';
        }
    }

    /**
     * Renders backend list filter.
     *
     * @param $label
     * @param $key
     * @param $values
     */
    public static function renderFilter($label, $key, $values)
    {
        //only add filter to post type you want
        if (isset($_GET['post_type']) && self::$postType == $_GET['post_type']) {
            ?>
            <select name="<?php echo 'admin_' . $key; ?>">
                <option value=""><?php echo $label; ?></option>
                <?php
                $filterValue = isset($_GET['admin_' . $key]) ? $_GET['admin_' . $key] : '';
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
            // Meta value filtering
            $query->query_vars['meta_query'] = array(
                'relation' => 'AND'
            );
            $meta_filters = [
                'type'        => 'admin_filter_type',
                'item-id'     => 'admin_filter_item',
                'location-id' => 'admin_filter_location'
            ];
            foreach ($meta_filters as $key => $filter) {
                if (
                    isset($_GET[$filter]) &&
                    $_GET[$filter] != ''
                ) {
                    $query->query_vars['meta_query'][] = array(
                        'key'   => $key,
                        'value' => $_GET[$filter]
                    );
                }
            }

            // Timerange filtering
            // Start date
            if (
                isset($_GET['admin_filter_startdate']) &&
                $_GET['admin_filter_startdate'] != ''
            ) {
                $query->query_vars['meta_query'][] = array(
                    'key'   => 'repetition-start',
                    'value' => strtotime($_GET['admin_filter_startdate']),
                    'compare' => ">="
                );
            }

            // End date
            if (
                isset($_GET['admin_filter_enddate']) &&
                $_GET['admin_filter_enddate'] != ''
            ) {
                $query->query_vars['meta_query'][] = array(
                    'key'   => 'repetition-end',
                    'value' => strtotime($_GET['admin_filter_enddate']),
                    'compare' => "<="
                );
            }
            
            // Post field filtering
            $post_filters = [
                'post_status' => 'admin_filter_post_status'
            ];
            foreach ($post_filters as $key => $filter) {
                if (
                    isset($_GET[$filter]) &&
                    $_GET[$filter] != ''
                ) {
                    $query->query_vars[$key] = $_GET[$filter];
                }
            }

        }
    }

}
