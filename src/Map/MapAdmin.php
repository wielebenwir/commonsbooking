<?php

namespace CommonsBooking\Map;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Map;

/**
 *
 **/
class MapAdmin
{

    const OPTION_KEYS = [
        'base_map',
        'show_scale',
        'map_height',
        'custom_no_locations_message',
        'zoom_min',
        'zoom_max',
        'zoom_start',
        'lat_start',
        'lon_start',
        'marker_map_bounds_initial',
        'marker_map_bounds_filter',
        'max_cluster_radius',
        'marker_tooltip_permanent',
        'custom_marker_media_id',
        'marker_icon_width',
        'marker_icon_height',
        'marker_icon_anchor_x',
        'marker_icon_anchor_y',
        'show_location_contact',
        'label_location_contact',
        'show_location_opening_hours',
        'label_location_opening_hours',
        'show_item_availability',
        'custom_marker_cluster_media_id',
        'marker_cluster_icon_width',
        'marker_cluster_icon_height',
        'address_search_bounds_left_bottom_lon',
        'address_search_bounds_left_bottom_lat',
        'address_search_bounds_right_top_lon',
        'address_search_bounds_right_top_lat',
        'show_location_distance_filter',
        'label_location_distance_filter',
        'show_item_availability_filter',
        'label_item_availability_filter',
        'label_item_category_filter',
        'item_draft_appearance',
        'marker_item_draft_media_id',
        'marker_item_draft_icon_width',
        'marker_item_draft_icon_height',
        'marker_item_draft_icon_anchor_x',
        'marker_item_draft_icon_anchor_y',
        'cb_items_available_categories',
        'cb_items_preset_categories',
    ];

    const EXPORT_CODE_VALUE_MIN_LENGTH = 10;
    const MAP_HEIGHT_VALUE_MIN = 100;
    const MAP_HEIGHT_VALUE_MAX = 5000;
    const ZOOM_VALUE_MIN = 1;
    const ZOOM_VALUE_MAX = 19;
    const LAT_VALUE_MIN = -90;
    const LAT_VALUE_MAX = 90;
    const LON_VALUE_MIN = -180;
    const LON_VALUE_MAX = 180;
    const MAX_CLUSTER_RADIUS_VALUE_MIN = 10;
    const MAX_CLUSTER_RADIUS_VALUE_MAX = 1000;

    const EXPORT_CODE_DEFAULT = "";
    const IMPORT_SOURCES_DEFAULT = [];
    const BASE_MAP_DEFAULT = 1;
    const SHOW_SCALE_DEFAULT = true;
    const MAP_HEIGHT_DEFAULT = 400;
    const CUSTOM_NO_LOCATIONS_MESSAGE_DEFAULT = '';
    const ENABLE_MAP_DATA_EXPORT_DEFAULT = false;
    const ZOOM_MIN_DEFAULT = 9;
    const ZOOM_MAX_DEFAULT = 19;
    const ZOOM_START_DEFAULT = 9;
    const LAT_START_DEFAULT = 52.49333;
    const LON_START_DEFAULT = 13.37933;
    const MARKER_MAP_BOUNDS_INITIAL_DEFAULT = false;
    const MARKER_MAP_BOUNDS_FILTER_DEFAULT = true;
    const MAX_CLUSTER_RADIUS_DEFAULT = 80;
    const MARKER_TOOLTIP_PERMANENT_DEFAULT = false;
    const CUSTOM_MARKER_MEDIA_ID_DEFAULT = null;
    const MARKER_ICON_WIDTH_DEFAULT = 0;
    const MARKER_ICON_HEIGHT_DEFAULT = 0;
    const MARKER_ICON_ANCHOR_X_DEFAULT = 0;
    const MARKER_ICON_ANCHOR_Y_DEFAULT = 0;
    const CUSTOM_MARKER_CLUSTER_MEDIA_ID_DEFAULT = null;
    const MARKER_CLUSTER_ICON_WIDTH_DEFAULT = 0;
    const MARKER_CLUSTER_ICON_HEIGHT_DEFAULT = 0;
    const SHOW_LOCATION_CONTACT_DEFAULT = false;
    const LABEL_LOCATION_CONTACT_DEFAULT = "";
    const SHOW_LOCATION_OPENING_HOURS_DEFAULT = false;
    const LABEL_LOCATION_OPENING_HOURS_DEFAULT = "";
    const ADDRESS_SEARCH_BOUNDS_LEFT_BOTTOM_LAT_DEFAULT = null;
    const ADDRESS_SEARCH_BOUNDS_LEFT_BOTTOM_LON_DEFAULT = null;
    const ADDRESS_SEARCH_BOUNDS_RIGHT_TOP_LAT_DEFAULT = null;
    const ADDRESS_SEARCH_BOUNDS_RIGHT_TOP_LON_DEFAULT = null;
    const SHOW_ITEM_AVAILABILITY_DEFAULT = false;
    const SHOW_LOCATION_DISTANCE_FILTER_DEFAULT = false;
    const LABEL_LOCATION_DISTANCE_FILTER_DEFAULT = "";
    const SHOW_ITEM_AVAILABILITY_FILTER_DEFAULT = false;
    const LABEL_ITEM_AVAILABILITY_FILTER_DEFAULT = "";
    const LABEL_ITEM_CATEGORY_FILTER_DEFAULT = "";
    const CB_ITEMS_AVAILABLE_CATEGORIES_DEFAULT = [];
    const CB_ITEMS_PRESET_CATEGORIES_DEFAULT = [];
    const ITEM_DRAFT_APPEARANCE_DEFAULT = 1;
    const MARKER_ITEM_DRAFT_MEDIA_ID_DEFAULT = null;
    const MARKER_ITEM_DRAFT_ICON_WIDTH_DEFAULT = 0;
    const MARKER_ITEM_DRAFT_ICON_HEIGHT_DEFAULT = 0;
    const MARKER_ITEM_DRAFT_ICON_ANCHOR_X_DEFAULT = 0;
    const MARKER_ITEM_DRAFT_ICON_ANCHOR_Y_DEFAULT = 0;

    //const MARKER_POPUP_CONTENT_DEFAULT = "'<b>' + location.location_name + '</b><br>' + location.address.street + '<br>' + location.address.zip + ' ' + location.address.city + '<p>' + location.opening_hours + '</p>'";

    public static $options;

    public static function add_meta_boxes()
    {
        self::add_settings_meta_box(
            'cb_map_admin',
            Map::__( 'Map Configuration', 'commonsbooking'));
    }

    public static function add_settings_meta_box($meta_box_id, $meta_box_title)
    {
        $plugin_prefix = 'cb_map_post_type_';

        $html_id_attribute = $plugin_prefix.$meta_box_id.'_meta_box';
        $callback          = array(MapAdmin::class, 'render_options_page');
        $show_on_post_type = 'cb_map';
        $box_placement     = 'normal';
        $box_priority      = 'high';

        add_meta_box(
            $html_id_attribute,
            $meta_box_title,
            $callback,
            $show_on_post_type,
            $box_placement,
            $box_priority
        );
    }

    /**
     *
     **/
    public static function get_options($cb_map_id = null, $force_reload = false)
    {
        self::load_options($cb_map_id, $force_reload);

        return self::$options;
    }

    public static function load_options($cb_map_id = null, $force_reload = false)
    {
        if ( ! isset(self::$options) || $force_reload) {
            if ($cb_map_id) {
                $options = get_post_meta($cb_map_id, 'cb_map_options', true);

                if ( ! is_array($options)) {
                    $options = [];
                }
            } else {
                $options = [];
            }

            self::$options = self::populate_option_defaults($options);
        }
    }

    public static function populate_option_defaults($options)
    {
        //var_dump($options);

        foreach (self::OPTION_KEYS as $key) {
            if ( ! isset($options[$key])) {
                $options[$key] = self::get_option_default($key);
            }
        }

        return $options;
    }

    private static function get_option_default($option_name)
    {

        $default_name = strtoupper($option_name).'_DEFAULT';

        $const_value = constant("self::$default_name");

        return isset($const_value) ? $const_value : null;
    }

    /**
     * sanitize and validate the options provided by input array
     **/
    public static function validate_options($cb_map_id)
    {
        self::load_options($cb_map_id);

        $validated_input = self::populate_option_defaults([]);

        if (isset($_POST['cb_map_options'])) {
            $input = $_POST['cb_map_options'];
        }

        //base_map
        if (isset($input['base_map']) && $input['base_map'] >= 1 && $input['base_map'] <= 4) {
            $validated_input['base_map'] = (int)$input['base_map'];
        }

        //show_scale
        $validated_input['show_scale'] = isset($input['show_scale']) ? true : false;

        //map_height
        if (isset($input['map_height']) && (int)$input['map_height'] >= self::MAP_HEIGHT_VALUE_MIN && $input['map_height'] <= self::MAP_HEIGHT_VALUE_MAX) {
            $validated_input['map_height'] = (int)$input['map_height'];
        }

        //custom_no_locations_message
        if (isset($input['custom_no_locations_message'])) {
            $validated_input['custom_no_locations_message'] = sanitize_text_field($input['custom_no_locations_message']);
        }

        //zoom_min
        if (isset($input['zoom_min']) && (int)$input['zoom_min'] >= self::ZOOM_VALUE_MIN && $input['zoom_min'] <= self::ZOOM_VALUE_MAX) {
            $validated_input['zoom_min'] = (int)$input['zoom_min'];
        }

        //zoom_max
        if (isset($input['zoom_max']) && (int)$input['zoom_max'] >= self::ZOOM_VALUE_MIN && $input['zoom_max'] <= self::ZOOM_VALUE_MAX) {
            if ((int)$input['zoom_max'] >= $validated_input['zoom_min']) {
                $validated_input['zoom_max'] = (int)$input['zoom_max'];
            } else {
                $validated_input['zoom_max'] = $validated_input['zoom_min'];
            }
        }

        //zoom_start
        if (isset($input['zoom_start']) && (int)$input['zoom_start'] >= self::ZOOM_VALUE_MIN && $input['zoom_start'] <= self::ZOOM_VALUE_MAX) {
            if ((int)$input['zoom_start'] >= $validated_input['zoom_min'] && (int)$input['zoom_start'] <= $validated_input['zoom_max']) {
                $validated_input['zoom_start'] = (int)$input['zoom_start'];
            } else {
                $validated_input['zoom_start'] = $validated_input['zoom_min'];
            }
        }

        //lat_start
        if (isset($input['lat_start']) && strlen($input['lat_start']) > 0 && (float)$input['lat_start'] >= self::LAT_VALUE_MIN && (float)$input['lat_start'] <= self::LAT_VALUE_MAX) {
            $validated_input['lat_start'] = (float)$input['lat_start'];
        }

        //lon_start
        if (isset($input['lon_start']) && strlen($input['lon_start']) > 0 && (float)$input['lon_start'] >= self::LON_VALUE_MIN && (float)$input['lon_start'] <= self::LON_VALUE_MAX) {
            $validated_input['lon_start'] = (float)$input['lon_start'];
        }

        //marker_map_bounds_initial
        $validated_input['marker_map_bounds_initial'] = isset($input['marker_map_bounds_initial']) ? true : false;

        //marker_map_bounds_filter
        $validated_input['marker_map_bounds_filter'] = isset($input['marker_map_bounds_filter']) ? true : false;

        //max_cluster_radius
        if (isset($input['max_cluster_radius']) && (int)$input['max_cluster_radius'] >= self::MAX_CLUSTER_RADIUS_VALUE_MIN && $input['max_cluster_radius'] <= self::MAX_CLUSTER_RADIUS_VALUE_MAX) {
            $validated_input['max_cluster_radius'] = (int)$input['max_cluster_radius'];
        }

        //marker_tooltip_permanent
        if (isset($input['marker_tooltip_permanent'])) {
            $validated_input['marker_tooltip_permanent'] = true;
        } else {
            $validated_input['marker_tooltip_permanent'] = false;
        }

        // custom_marker_media_id
        if (isset($input['custom_marker_media_id'])) {
            $validated_input['custom_marker_media_id'] = abs((int)$input['custom_marker_media_id']);
        }

        //marker_icon_width
        if (isset($input['marker_icon_width'])) {
            $validated_input['marker_icon_width'] = abs((float)$input['marker_icon_width']);
        }

        //marker_icon_height
        if (isset($input['marker_icon_height'])) {
            $validated_input['marker_icon_height'] = abs((float)$input['marker_icon_height']);
        }

        //marker_icon_anchor_x
        if (isset($input['marker_icon_anchor_x'])) {
            $validated_input['marker_icon_anchor_x'] = (float)$input['marker_icon_anchor_x'];
        }

        //marker_icon_anchor_y
        if (isset($input['marker_icon_anchor_y'])) {
            $validated_input['marker_icon_anchor_y'] = (float)$input['marker_icon_anchor_y'];
        }

        //show_location_contact
        if (isset($input['show_location_contact'])) {
            $validated_input['show_location_contact'] = true;
        } else {
            $validated_input['show_location_contact'] = false;
        }

        //show_location_opening_hours
        if (isset($input['show_location_opening_hours'])) {
            $validated_input['show_location_opening_hours'] = true;
        } else {
            $validated_input['show_location_opening_hours'] = false;
        }

        //label_location_opening_hours
        if (isset($input['label_location_opening_hours']) && strlen($input['label_location_opening_hours']) > 0) {
            $validated_input['label_location_opening_hours'] = sanitize_text_field($input['label_location_opening_hours']);
        }

        //show_item_availability
        if (isset($input['show_item_availability'])) {
            $validated_input['show_item_availability'] = true;
        } else {
            $validated_input['show_item_availability'] = false;
        }

        // custom_marker_cluster_media_id
        if (isset($input['custom_marker_cluster_media_id'])) {
            $validated_input['custom_marker_cluster_media_id'] = abs((int)$input['custom_marker_cluster_media_id']);
        }

        //label_location_contact
        if (isset($input['label_location_contact']) && strlen($input['label_location_contact']) > 0) {
            $validated_input['label_location_contact'] = sanitize_text_field($input['label_location_contact']);
        }

        //marker_cluster_icon_width
        if (isset($input['marker_cluster_icon_width'])) {
            $validated_input['marker_cluster_icon_width'] = abs((float)$input['marker_cluster_icon_width']);
        }

        //marker_cluster_icon_height
        if (isset($input['marker_cluster_icon_height'])) {
            $validated_input['marker_cluster_icon_height'] = abs((float)$input['marker_cluster_icon_height']);
        }

        //item_draft_appearance
        if (isset($input['item_draft_appearance']) && $input['item_draft_appearance'] >= 1 && $input['item_draft_appearance'] <= 3) {
            $validated_input['item_draft_appearance'] = $input['item_draft_appearance'];
        }

        // marker_item_draft_media_id
        if (isset($input['marker_item_draft_media_id'])) {
            $validated_input['marker_item_draft_media_id'] = abs((int)$input['marker_item_draft_media_id']);
        }

        //marker_item_draft_icon_width
        if (isset($input['marker_item_draft_icon_width'])) {
            $validated_input['marker_item_draft_icon_width'] = abs((float)$input['marker_item_draft_icon_width']);
        }

        //marker_item_draft_icon_height
        if (isset($input['marker_item_draft_icon_height'])) {
            $validated_input['marker_item_draft_icon_height'] = abs((float)$input['marker_item_draft_icon_height']);
        }

        //marker_item_draft_icon_anchor_x
        if (isset($input['marker_item_draft_icon_anchor_x'])) {
            $validated_input['marker_item_draft_icon_anchor_x'] = (float)$input['marker_item_draft_icon_anchor_x'];
        }

        //marker_item_draft_icon_anchor_y
        if (isset($input['marker_item_draft_icon_anchor_y'])) {
            $validated_input['marker_item_draft_icon_anchor_y'] = (float)$input['marker_item_draft_icon_anchor_y'];
        }

        //show_location_distance_filter
        if (isset($input['show_location_distance_filter'])) {
            $validated_input['show_location_distance_filter'] = true;
        } else {
            $validated_input['show_location_distance_filter'] = false;
        }

        //address_search_bounds_left_bottom_lat
        if (isset($input['address_search_bounds_left_bottom_lat']) && strlen($input['address_search_bounds_left_bottom_lat']) > 0 && (float)$input['address_search_bounds_left_bottom_lat'] >= self::LAT_VALUE_MIN && (float)$input['address_search_bounds_left_bottom_lat'] <= self::LAT_VALUE_MAX) {
            $validated_input['address_search_bounds_left_bottom_lat'] = (float)$input['address_search_bounds_left_bottom_lat'];
        }

        if (isset($input['address_search_bounds_left_bottom_lon']) && strlen($input['address_search_bounds_left_bottom_lon']) > 0 && (float)$input['address_search_bounds_left_bottom_lon'] >= self::LON_VALUE_MIN && (float)$input['address_search_bounds_left_bottom_lon'] <= self::LON_VALUE_MAX) {
            $validated_input['address_search_bounds_left_bottom_lon'] = (float)$input['address_search_bounds_left_bottom_lon'];
        }

        //address_search_bounds_right_top_lat
        if (isset($input['address_search_bounds_right_top_lat']) && strlen($input['address_search_bounds_right_top_lat']) > 0 && (float)$input['address_search_bounds_right_top_lat'] >= self::LAT_VALUE_MIN && (float)$input['address_search_bounds_right_top_lat'] <= self::LAT_VALUE_MAX) {
            $validated_input['address_search_bounds_right_top_lat'] = (float)$input['address_search_bounds_right_top_lat'];
        }

        //address_search_bounds_right_top_lon
        if (isset($input['address_search_bounds_right_top_lon']) && strlen($input['address_search_bounds_right_top_lon']) > 0 && (float)$input['address_search_bounds_right_top_lon'] >= self::LON_VALUE_MIN && (float)$input['address_search_bounds_right_top_lon'] <= self::LON_VALUE_MAX) {
            $validated_input['address_search_bounds_right_top_lon'] = (float)$input['address_search_bounds_right_top_lon'];
        }

        //label_location_distance_filter
        if (isset($input['label_location_distance_filter']) && strlen($input['label_location_distance_filter']) > 0) {
            $validated_input['label_location_distance_filter'] = sanitize_text_field($input['label_location_distance_filter']);
        }

        //show_item_availability_filter
        if (isset($input['show_item_availability_filter'])) {
            $validated_input['show_item_availability_filter'] = true;
        } else {
            $validated_input['show_item_availability_filter'] = false;
        }

        //label_item_availability_filter
        if (isset($input['label_item_availability_filter']) && strlen($input['label_item_availability_filter']) > 0) {
            $validated_input['label_item_availability_filter'] = sanitize_text_field($input['label_item_availability_filter']);
        }

        //label_item_category_filter
        if (isset($input['label_item_category_filter']) && strlen($input['label_item_category_filter']) > 0) {
            $validated_input['label_item_category_filter'] = sanitize_text_field($input['label_item_category_filter']);
        }

        //cb_items_available_categories
        $category_terms = get_terms([
            'taxonomy'   => Item::$postType . 's_category',
            'hide_empty' => false,
        ]);
        $valid_term_ids = [];
        foreach ($category_terms as $category_term) {
            $valid_term_ids[] = $category_term->term_id;
        }

        if (isset($input['cb_items_available_categories'])) {
            //first element has to be a filter group and has to contain at least one category
            $array_keys = array_keys($input['cb_items_available_categories']);
            if (count($input['cb_items_available_categories']) > 1 && substr($array_keys[0], 0,
                    1) == 'g' && substr($array_keys[1], 0, 1) != 'g') {
                foreach ($input['cb_items_available_categories'] as $key => $value) {
                    //filter group
                    if (substr($key, 0, 1) == 'g') {
                        $validated_input['cb_items_available_categories'][$key] = sanitize_text_field($value);
                    } //custom markup for category
                    else {
                        if (in_array((int)$key, $valid_term_ids)) {
                            $validated_input['cb_items_available_categories'][$key] = self::strip_script_tags($value);
                        }
                    }
                }
            }
        }

        //cb_items_preset_categories
        if (isset($input['cb_items_preset_categories'])) {
            foreach ($input['cb_items_preset_categories'] as $cb_items_category_id) {
                if (in_array((int)$cb_items_category_id, $valid_term_ids)) {
                    $validated_input['cb_items_preset_categories'][] = $cb_items_category_id;
                }
            }
        }

        update_post_meta($cb_map_id, 'cb_map_options', $validated_input);

        return $validated_input;
    }

    public static function strip_script_tags($input)
    {
        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
    }

    /**
     * asynchronously import locations from all sources of given map
     **/
    public static function start_import_from_all_sources_of_map($cb_map_id)
    {
        $url       = get_site_url(null, '', null).'/wp-admin/admin-ajax.php';
        $auth_code = CB_Map::create_import_auth_code();

        update_post_meta($cb_map_id, 'cb_map_import_auth_code', $auth_code);

        $args = [
            'blocking' => false,
            'body'     => [
                'action'    => 'cb_map_location_import_of_map',
                'cb_map_id' => $cb_map_id,
                'auth_code' => $auth_code,
            ],
        ];

        wp_safe_remote_post($url, $args);

    }

    public static function render_options_page($post)
    {
        //wp_nonce_field( basename( __FILE__ ), 'cb_map_post_type_meta_box_nonce' );

        $cb_map_id = $post->ID;

        wp_enqueue_media();

        //load image upload script
        $script_path = COMMONSBOOKING_MAP_ASSETS_URL.'js/cb-map-marker-upload.js';
        echo '<script src="'.$script_path.'"></script>';

        //map translation
        $translation = [
            'SELECT_IMAGE'              => Map::__('Select an image', 'commonsbooking'),
            'SAVE'                      => Map::__('save', 'commonsbooking'),
            'MARKER_IMAGE_MEASUREMENTS' => Map::__('measurements', 'commonsbooking'),
        ];
        echo '<script>cb_map_marker_upload.translation = '.json_encode($translation).';</script>';

        //available categories
        $available_categories_args             = [
            'taxonomy'      => 'cb_items_category',
            'echo'          => false,
            'checked_ontop' => false,
            'selected_cats' => array_keys(self::get_option($cb_map_id, 'cb_items_available_categories')),
        ];
        $available_categories_checklist_markup = wp_terms_checklist(0, $available_categories_args);
        $available_categories_checklist_markup = str_replace('name="tax_input[cb_items_category][]"',
            'class="cb_items_available_category_choice"', $available_categories_checklist_markup);
        $available_categories_checklist_markup = str_replace('id="in-cb_items_category-',
            'id="cb_items_available_category-', $available_categories_checklist_markup);

        //rearrange to nummeric array, because object property order isn't stable in js
        $cb_items_available_categories = self::get_option($cb_map_id, 'cb_items_available_categories');
        $available_categories          = [];
        foreach ($cb_items_available_categories as $id => $content) {
            $available_categories[] = [
                'id'      => (string)$id,
                'content' => $content,
            ];
        }

        //preset categories
        $preset_categories_args             = [
            'taxonomy'      => 'cb_items_category',
            'echo'          => false,
            'checked_ontop' => false,
            'selected_cats' => self::get_option($cb_map_id, 'cb_items_preset_categories'),
        ];
        $preset_categories_checklist_markup = wp_terms_checklist(0, $preset_categories_args);
        $preset_categories_checklist_markup = str_replace('name="tax_input[cb_items_category]',
            'name="cb_map_options[cb_items_preset_categories]', $preset_categories_checklist_markup);
        $preset_categories_checklist_markup = str_replace('id="in-cb_items_category-', 'id="cb_items_preset_category-',
            $preset_categories_checklist_markup);


        wp_enqueue_style('cb_map_admin_css', COMMONSBOOKING_MAP_ASSETS_URL.'css/cb-map-admin.css');

        include_once(COMMONSBOOKING_MAP_PATH.'templates/admin-page-template.php');
    }

    /**
     * option getter
     **/
    public static function get_option($cb_map_id = null, $key)
    {
        self::load_options($cb_map_id);

        return self::$options[$key];
    }

}

?>
