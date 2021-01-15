<?php


namespace CommonsBooking\Wordpress\CustomPostType;


use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Map\MapSettings;
use CommonsBooking\Map\MapShortcode;
use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Settings\Settings;

class Map extends CustomPostType
{

    /**
     * @var string
     */
    public static $postType = 'cb_map';

    /**
     * Map constructor.
     */
    public function __construct()
    {
        $cb_map_settings = new MapSettings();
        $cb_map_settings->prepare_settings();
        if ($cb_map_settings->get_option('booking_page_link_replacement')) {
            add_action('wp_enqueue_scripts', array(Map::class, 'replace_map_link_target'), 11);
        }

        // Setting role permissions
        add_action('admin_init', array($this, 'addRoleCaps'), 999);

        // Add shortcodes
        add_shortcode('cb_map', array(MapShortcode::class, 'execute') );

        // Add actions
        add_action('save_post_cb_map', array(MapAdmin::class, 'validate_options'), 10, 3);
        add_action('add_meta_boxes_cb_map', array(MapAdmin::class, 'add_meta_boxes'));
        add_action('add_meta_boxes_cb_map', array(MapAdmin::class, 'add_meta_boxes'));
    }

    /**
     * @param $text
     * @param string $domain
     * @param null $default
     *
     * @return mixed
     */
    public static function __($text, $domain = 'default', $default = null)
    {

        $translation = \__($text, $domain);

        if ($translation == $text && isset($default)) {
            $translation = $default;
        }

        return $translation;
    }

    public function getArgs()
    {
        $labels = array(
            'name'               => self::__('Maps', 'commonsbooking'),
            'singular_name'      => self::__('Map', 'commonsbooking'),
            'add_new'            => self::__('create CB map', 'commonsbooking'),
            'add_new_item'       => self::__('create Commons Booking map', 'commonsbooking'),
            'edit_item'          => self::__('edit Commons Booking map', 'commonsbooking'),
            'new_item'           => self::__('create CB map', 'commonsbooking'),
            'view_item'          => self::__('view CB map', 'commonsbooking'),
            'search_items'       => self::__('search CB maps', 'commonsbooking'),
            'not_found'          => self::__('no Commons Booking map found', 'commonsbooking'),
            'not_found_in_trash' => self::__('no Commons Booking map found in the trash', 'commonsbooking'),
            'parent_item_colon'  => self::__('parent CB maps', 'commonsbooking'),
        );

        $supports = array(
            'title',
            'author',
        );

        $args = array(
            'labels'            => $labels,

            // Sichtbarkeit des Post Types
            'public'            => true,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'           => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'      => false,

            // Position im Menu
            'menu_position'     => 5,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar' => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus' => true,
            'hierarchical'      => false,
            'description'       => self::__('Maps to show Commons Booking Locations and their Items', 'commonsbooking'),
            'supports'          => $supports,
            'menu_icon'           => 'dashicons-location',
            'publicly_queryable'  => false,
            'exclude_from_search' => false,
            'has_archive'         => false,
            'query_var'           => false,
            'can_export'          => false,
            'delete_with_user'    => false,
            'capability_type'     => array(self::$postType, self::$postType.'s'),
        );
        return $args;
    }

    public static function getView()
    {
        return new \CommonsBooking\View\Map();
    }

    /**
     * enforce the replacement of the original (google maps) link target on cb_item booking pages
     **/
    public static function replace_map_link_target()
    {
        global $post;
        $cb_item = 'cb_items';
        if (is_object($post) && $post->post_type == $cb_item) {
            $itemId = $post->ID;

            //get timeframes of item
            $cb_data    = new CB_Data();
            $date_start = date('Y-m-d'); // current date
            $timeframes = $cb_data->get_timeframes($post->ID, $date_start);

            $geo_coordinates = [];
            if ($timeframes) {
                foreach ($timeframes as $timeframe) {
                    $geo_coordinates[$timeframe['id']] = [
                        'lat' => get_post_meta($timeframe['location_id'], 'cb-map_latitude', true),
                        'lon' => get_post_meta($timeframe['location_id'], 'cb-map_longitude', true),
                    ];
                }
            }

            wp_register_script('cb_map_replace_map_link_js', COMMONSBOOKING_MAP_ASSETS_URL.'js/cb-map-replace-link.js');

            wp_add_inline_script('cb_map_replace_map_link_js',
                "cb_map_timeframes_geo = ".json_encode($geo_coordinates).";");

            wp_enqueue_script('cb_map_replace_map_link_js');
        }
    }

    /**
     * returns the directory path of the given plugin main file relative to the plugin directory,
     * i.e. commons-booking/commons-booking.php for $plugin_name = commons-booking.php
     **/
    public static function get_active_plugin_directory($plugin_name)
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        foreach ($active_plugins as $plugin) {
            $plugin_file_path = COMMONSBOOKING_MAP_PATH.'../'.$plugin;
            if (strpos($plugin, $plugin_name) !== false && file_exists($plugin_file_path)) {
                var_dump($plugin);

                return dirname($plugin);
            }
        }

        return null;
    }

    /**
     * load all timeframes from db (that end in the future and it's item's status is 'publish')
     **/
    public static function get_timeframes()
    {
        $timeframes = Timeframe::get(
            [],
            [],
            [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
            false,
            true,
            time()
        );

        /** @var \CommonsBooking\Model\Timeframe $timeframe */
        foreach ($timeframes as $timeframe) {
            $item     = $timeframe->getItem();
            $location = $timeframe->getLocation();

            if ($item && $location) {
                $item_desc = $item->getMeta(COMMONSBOOKING_METABOX_PREFIX.'location_info');
                $thumbnail = get_the_post_thumbnail_url($item, 'thumbnail');

                $result[] = [
                    'location_id' => $timeframe->getLocation()->ID,
                    'item'        => [
                        'id'         => $item->ID,
                        'name'       => $item->post_title,
                        'short_desc' => $item_desc,
                        'link'       => get_permalink($item),
                        'thumbnail'  => $thumbnail ? $thumbnail : null,
                        'status'     => $item->post_status,
                    ],
                    'date_start'  => $timeframe->getStartDate(),
                    'date_end'    => $timeframe->getEndDate(),
                ];
            }
        }

        return $result;
    }

    public static function has_item_valid_status($item, $item_draft_appearance)
    {

        if ($item_draft_appearance == 1) {
            return $item->post_status == 'publish';
        }
        if ($item_draft_appearance == 2) {
            return $item->post_status != 'publish';
        }
        if ($item_draft_appearance == 3) {
            return true;
        }
    }

    /**
     * get geo data from location metadata
     */
    public static function get_locations($cb_map_id)
    {
        $locations = [];

        $show_location_contact       = MapAdmin::get_option($cb_map_id, 'show_location_contact');
        $show_location_opening_hours = MapAdmin::get_option($cb_map_id, 'show_location_opening_hours');

        $args = [
            'post_type'      => Location::$postType,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'          => COMMONSBOOKING_METABOX_PREFIX.'geo_longitude',
                    'meta_compare' => 'EXISTS',
                ],
            ],
        ];

        $locationObjects = \CommonsBooking\Repository\Location::get(
            $args,
            true

        );

        /** @var \CommonsBooking\Model\Location $post */
        foreach ($locationObjects as $post) {
            $location_meta = get_post_meta($post->ID, null, true);

            //set serialized empty array if not set
            $closed_days = isset($location_meta['commons-booking_location_closeddays']) ? $location_meta['commons-booking_location_closeddays'][0] : 'a:0:{}';

            $items = [];
            foreach (Item::getByLocation($post->ID, true) as $item) {
                $item_terms = wp_get_post_terms( $item->ID, \CommonsBooking\Wordpress\CustomPostType\Item::$postType . 's_category');
                if(is_array($item_terms) && count($item_terms)) {
                    $item_terms = array_map(
                        function($item) {
                            return $item->term_id;
                        },
                        $item_terms
                    );
                }

                $thumbnail = get_the_post_thumbnail_url($item->ID, 'thumbnail');
                $items[] = [
                    'id'         => $item->ID,
                    'name'       => $item->post_title,
                    'short_desc' => has_excerpt($item->ID) ? wp_strip_all_tags(get_the_excerpt($item->ID)) : "",
                    'status'     => $item->post_status,
                    'terms'      => $item_terms,
                    'link'       => add_query_arg('location', $post->ID, get_permalink($item->ID)),
                    'thumbnail' => $thumbnail ? $thumbnail : null,
                ];
            }

            $locations[$post->ID] = [
                'lat'           => (float)$location_meta[COMMONSBOOKING_METABOX_PREFIX.'geo_latitude'][0],
                'lon'           => (float)$location_meta[COMMONSBOOKING_METABOX_PREFIX.'geo_longitude'][0],
                'location_name' => $post->post_title,
                'closed_days'   => unserialize($closed_days),
                'address'       => [
                    'street' => $location_meta[COMMONSBOOKING_METABOX_PREFIX.'location_street'][0],
                    'city'   => $location_meta[COMMONSBOOKING_METABOX_PREFIX.'location_city'][0],
                    'zip'    => $location_meta[COMMONSBOOKING_METABOX_PREFIX.'location_postcode'][0],
                ],
                'items'         => $items,
            ];

            if ($show_location_contact) {
                $locations[$post->ID]['contact'] = $location_meta[COMMONSBOOKING_METABOX_PREFIX . 'location_contact'][0];
            }

            //@TODO: Check field -> we don't have such a field at the moment.
//            if ($show_location_opening_hours) {
//                $locations[$post->ID]['opening_hours'] = $location_meta['commons-booking_location_openinghours'][0];
//            }
        }

        return $locations;
    }

    public static function get_cb_items_category_groups($preset_categories)
    {
        $groups         = [];
        $category_terms = get_terms([
            'taxonomy'   => 'cb_items_category',
            'hide_empty' => false,
        ]);

        foreach ($category_terms as $term) {
            if (in_array($term->term_id, $preset_categories)) {
                if ( ! isset($groups[$term->parent])) {
                    $groups[$term->parent] = [];
                }
                $groups[$term->parent][] = $term->term_id;

            }
        }

        return $groups;
    }

    /**
     * basic check if the given string is valid JSON
     **/
    public static function is_json($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * clean up the location data
     *
     * @param $locations
     * @param $linebreak_replacement
     *
     * @return mixed
     */
    public static function cleanup_location_data($locations, $linebreak_replacement)
    {
        foreach ($locations as $key => &$location) {
            $location = self::cleanup_location_data_entry($location, $linebreak_replacement);
        }

        return $locations;
    }

    /**
     * recursive clean up of location data entries
     *
     * @param $value
     * @param $linebreak_replacement
     *
     * @return mixed|string|string[]|null
     */
    public static function cleanup_location_data_entry($value, $linebreak_replacement)
    {

        if (is_string($value)) {
            $value = preg_replace('/(\r\n)|\n|\r/', $linebreak_replacement, $value); //replace linebreaks
            $value = preg_replace('/<.*(.*?)/', '', $value); //strip off everything that smell's like HTML
        }

        if (is_array($value)) {
            foreach ($value as &$child_value) {
                $child_value = self::cleanup_location_data_entry($child_value, $linebreak_replacement);
            }
        }

        return $value;
    }
}
