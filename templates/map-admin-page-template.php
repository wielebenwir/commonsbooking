<?php

use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Wordpress\CustomPostType\Map;

?>
<div class="inside">

    <p><?php echo commonsbooking_sanitizeHTML( __( 'These settings help you to configure the usage and appearance of Commons Booking Map.' ,'commonsbooking')); ?></p>

    <div class="option-group" id="option-group-map-presentation">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Map Presentation' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'shortcode' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('with this shortcode the map can be included in posts or pages' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>[cb_map id=<?php echo  commonsbooking_sanitizeHTML( $cb_map_id ) ?>]</td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'base map' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the base map defines the rendering style of the map tiles' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <?php $selected_base_map = commonsbooking_sanitizeArrayorString(MapAdmin::get_option($cb_map_id, 'base_map'), 'intval'); ?>
                        <select name="cb_map_options[base_map]">
                            <option value="1" <?php echo  $selected_base_map == 1 ? 'selected' : '' ?>><?php echo commonsbooking_sanitizeHTML( __( 'OSM - mapnik' ,'commonsbooking')); ?></option>
                            <option value="2" <?php echo  $selected_base_map == 2 ? 'selected' : '' ?>><?php echo commonsbooking_sanitizeHTML( __( 'OSM - german style' ,'commonsbooking')); ?></option>
							<!-- Reenable the map styles if needed -->
                            <!--<option value="3" <?php /* echo  $selected_base_map == 3 ? 'selected' : '' */ ?>><?php /* echo commonsbooking_sanitizeHTML( __( 'OSM - hike and bike' ,'commonsbooking')); */ ?></option>-->
                            <!--<option value="4" <?php /* echo  $selected_base_map == 4 ? 'selected' : '' */ ?>><?php /* echo commonsbooking_sanitizeHTML( __( 'OSM - lokaler (min. zoom: 9)' ,'commonsbooking')); */ ?></option>-->
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'show scale' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('show the current scale in the left bottom corner of the map' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="checkbox" name="cb_map_options[show_scale]" <?php echo  commonsbooking_sanitizeHTML(MapAdmin::get_option($cb_map_id, 'show_scale')) ? 'checked="checked"' : '' ?> value="on">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'map height' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the height the map is rendered with - the width is the same as of the parent element' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::MAP_HEIGHT_VALUE_MIN ) ?>"
                               max="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::MAP_HEIGHT_VALUE_MAX ) ?>" name="cb_map_options[map_height]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'map_height')); ?>" size="4"> px
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'no locations message' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('in case a user filters locations and gets no result, a message is shown - here the text can be customized' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><textarea
                                name="cb_map_options[custom_no_locations_message]"><?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                'custom_no_locations_message')); ?></textarea></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'enable data export' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to enable a button that allows the export of map data (geojson format)' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[enable_map_data_export]" <?php echo  commonsbooking_sanitizeHTML(MapAdmin::get_option($cb_map_id,'enable_map_data_export')) ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-zoom">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Zoom' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'min. zoom level' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the minimal zoom level a user can choose' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MIN  )?>" max="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MAX ) ?>"
                               name="cb_map_options[zoom_min]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'zoom_min')); ?>" size="3"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'max. zoom level' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the maximal zoom level a user can choose' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MIN ) ?>" max="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MAX ) ?>"
                               name="cb_map_options[zoom_max]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'zoom_max')); ?>" size="3"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'start zoom level' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the zoom level that will be set when the map is loaded' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MIN ) ?>" max="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::ZOOM_VALUE_MAX ) ?>"
                               name="cb_map_options[zoom_start]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'zoom_start')); ?>" size="3"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'enable scroll wheel zoom' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('when activated users can zoom the map using the scroll wheel' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                              name="cb_map_options[scrollWheelZoom]" <?php echo  commonsbooking_sanitizeHTML(MapAdmin::get_option($cb_map_id, 'scrollWheelZoom')) ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-positioning-start">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Map Positioning (center) at Intialization' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'start latitude' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the latitude of the map center when the map is loaded' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[lat_start]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'lat_start')); ?>" size="10"></td>
                </tr>

                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'start longitude' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the longitude of the map center when the map is loaded' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[lon_start]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'lon_start')); ?>" size="10"></td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-adaptive-map-section">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Adaptive Map Section' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'initial adjustment to marker bounds' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('adjust map section to bounds of shown markers automatically when map is loaded' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="checkbox"
                               name="cb_map_options[marker_map_bounds_initial]" <?php echo  MapAdmin::get_option($cb_map_id, 'marker_map_bounds_initial') ? 'checked="checked"' : '' ?> value="on">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'adjustment to marker bounds on filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('adjust map section to bounds of shown markers automatically when filtered by users' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="checkbox"
                               name="cb_map_options[marker_map_bounds_filter]" <?php echo  MapAdmin::get_option($cb_map_id, 'marker_map_bounds_filter') ? 'checked="checked"' : '' ?> value="on">
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-tooltip">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Marker Tooltip' ,'commonsbooking')); ?></summary>

            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'show permanently' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the marker tooltips permanently' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[marker_tooltip_permanent]" <?php echo  MapAdmin::get_option($cb_map_id, 'marker_tooltip_permanent' ,'commonsbooking') ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
            </table>
        </details>
    </div>
    <div class="option-group" id="option-group-popup">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Marker Popup' ,'commonsbooking')); ?></summary>
            <table class="text-left">
			<!-- 
				deactivated popup information because we dont want to show contact infos before booking and do not want to display long pickup instructions here
				needs to be optmized in future version
				
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __('show location opening hours' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the opening hours of locations in the marker popup' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[show_location_opening_hours]" <?php echo  MapAdmin::get_option($cb_map_id, 'show_location_opening_hours') ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'label for opening hours' ,'commonsbooking')); ?>
                        :
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('alternative label for the opening hours of locations in the marker popup' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[label_location_opening_hours]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'opening hours' ,'commonsbooking')); ?>"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'label_location_opening_hours') ); ?>"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'show location contact' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the location contact details in the marker popup' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[show_location_contact]" <?php echo  MapAdmin::get_option($cb_map_id, 'show_location_contact') ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'label for opening hours' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('alternative label for the contact information of locations in the marker popup' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[label_location_contact]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'opening hours' ,'commonsbooking')); ?>"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'label_location_contact') ); ?>"></td>
                </tr>
						-->
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'show item availability' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the item availability in the marker popup' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="checkbox"
                               name="cb_map_options[show_item_availability]" <?php echo  MapAdmin::get_option($cb_map_id,'show_item_availability') ? 'checked="checked"' : '' ?> value="on">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'Max. available days in popup', 'commonsbooking' )); ?>:
                        <span
                            class="dashicons dashicons-editor-help"
                            title="<?php
                                echo commonsbooking_sanitizeHTML(
                                        __(
                                            'Set how many days are displayed on the popup (starting from today)',
                                            'commonsbooking'
                                        )
                                ); ?>">
                        </span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::AVAILABILITY_MAX_DAYS_TO_SHOW_DEFAULT_MIN ) ?>" name="cb_map_options[availability_max_days_to_show]"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'availability_max_days_to_show') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'Maximum days to choose in map availabilty filter ', 'commonsbooking' ) ); ?>:
                        <span
                                class="dashicons dashicons-editor-help"
                                title="<?php
                                echo commonsbooking_sanitizeHTML(
                                    __( 'Notice: Defines the maximun days a user can choose in the availabilty filter in frontend map', 'commonsbooking' )
                                ); ?>">
                        </span>
                    </th>
                    <td><input type="number" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::AVAILABILITY_MAX_DAY_COUNT_DEFAULT ) ?>" name="cb_map_options[availability_max_day_count]"
                               value="<?php echo commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'availability_max_day_count') ); ?>">
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-custom-marker">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Custom Marker' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'image file' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the default marker icon can be replaced by a custom image' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input id="select-marker-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'select' ,'commonsbooking')); ?>"/>
                        <input id="remove-marker-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'remove' ,'commonsbooking')); ?>"/>
                    </td>
                </tr>
                <tr id="marker-image-preview-settings" class="display-none">
                    <td>
                        <div>
                            <img id="marker-image-preview"
                                 src="<?php echo  wp_get_attachment_url(MapAdmin::get_option($cb_map_id,
                                     'custom_marker_media_id')); ?>">
                        </div>
                        <input type="hidden" name="cb_map_options[custom_marker_media_id]"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'custom_marker_media_id') ); ?>">
                    </td>
                    <td>
                        <div id="marker-image-preview-measurements"></div>
                    </td>
                </tr>
                <tr id="marker-icon-size" class="display-none">
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'icon size' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the size of the custom marker icon image as it is shown on the map' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[marker_icon_width]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_icon_width')); ?>" size="3">
                        x
                        <input type="text" name="cb_map_options[marker_icon_height]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_icon_height')); ?>"
                               size="3"> px
                    </td>

                </tr>
                <tr id="marker-icon-anchor" class="display-none">
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'anchor point' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the position of the anchor point of the icon image, seen from the left top corner of the icon, often it is half of the width and full height of the icon size - this point is used to place the marker on the geo coordinates' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[marker_icon_anchor_x]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_icon_anchor_x')); ?>"
                               size="3"> x
                        <input type="text" name="cb_map_options[marker_icon_anchor_y]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_icon_anchor_y')); ?>"
                               size="3"> px
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-cluster">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Cluster' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'max. cluster radius' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('combine markers to a cluster within given radius - 0 for deactivation' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="number" size="3" step="10" min="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::MAX_CLUSTER_RADIUS_VALUE_MIN ) ?>"
                               max="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::MAX_CLUSTER_RADIUS_VALUE_MAX ) ?>"
                               name="cb_map_options[max_cluster_radius]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'max_cluster_radius')); ?>"> px
                    </td>
                </tr>
            </table>
        </details>

        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Custom Cluster Marker' ,'commonsbooking')); ?></summary>

            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'image file' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the default marker icon can be replaced by a custom image' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input id="select-marker-cluster-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'select' ,'commonsbooking')); ?>"/>
                        <input id="remove-marker-cluster-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'remove' ,'commonsbooking')); ?>"/>
                    </td>
                </tr>
                <tr id="marker-cluster-image-preview-settings" class="display-none">
                    <td>
                        <div>
                            <img id="marker-cluster-image-preview"
                                 src="<?php echo  wp_get_attachment_url(MapAdmin::get_option($cb_map_id,
                                     'custom_marker_cluster_media_id')); ?>">
                        </div>
                        <input type="hidden" name="cb_map_options[custom_marker_cluster_media_id]"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'custom_marker_cluster_media_id') ); ?>">
                    </td>
                    <td>
                        <div id="marker-cluster-image-preview-measurements"></div>
                    </td>
                </tr>
                <tr id="marker-cluster-icon-size" class="display-none">
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'icon size' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the size of the custom marker icon image as it is shown on the map' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[marker_cluster_icon_width]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_cluster_icon_width')); ?>"
                               size="3"> x
                        <input type="text" name="cb_map_options[marker_cluster_icon_height]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'marker_cluster_icon_height')); ?>"
                               size="3"> px
                    </td>

                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-item-status-appearance">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Appearance by Item Status' ,'commonsbooking')); ?></summary>

            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'appearance' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('how locations with items that are in draft status should be handled' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <?php $item_draft_appearance = commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'item_draft_appearance') ); ?>
                        <select id="item_draft_appearance" name="cb_map_options[item_draft_appearance]">
                            <option value="1" <?php echo  $item_draft_appearance == 1 ? 'selected' : '' ?>><?php echo commonsbooking_sanitizeHTML( __( "don't show drafts", 'commonsbooking') ); ?></option>
                            <option value="2" <?php echo  $item_draft_appearance == 2 ? 'selected' : '' ?>><?php echo commonsbooking_sanitizeHTML( __( "show only drafts", 'commonsbooking') ); ?></option>
                            <option value="3" <?php echo  $item_draft_appearance == 3 ? 'selected' : '' ?>><?php echo commonsbooking_sanitizeHTML( __( "show all together", 'commonsbooking') ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-item-status-appearance">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Custom Item Draft Marker' ,'commonsbooking') ); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'image file' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the default marker icon can be replaced by a custom image' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input id="select-marker-item-draft-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'select' ,'commonsbooking')); ?>"/>
                        <input id="remove-marker-item-draft-image-button" type="button" class="button"
                               value="<?php echo commonsbooking_sanitizeHTML( __( 'remove' ,'commonsbooking')); ?>"/>
                    </td>
                </tr>
                <tr id="marker-item-draft-image-preview-settings" class="display-none">
                    <td>
                        <div>
                            <img id="marker-item-draft-image-preview"
                                 src="<?php echo  esc_url(wp_get_attachment_url(MapAdmin::get_option($cb_map_id,
                                     'marker_item_draft_media_id'))); ?>">
                        </div>
                        <input type="hidden" name="cb_map_options[marker_item_draft_media_id]"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'marker_item_draft_media_id') ); ?>">
                    </td>
                    <td>
                        <div id="marker-item-draft-image-preview-measurements"></div>
                    </td>
                </tr>
                <tr id="marker-item-draft-icon-size" class="display-none">
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'icon size' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the size of the custom marker icon image as it is shown on the map' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[marker_item_draft_icon_width]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'marker_item_draft_icon_width')); ?>" size="3"> x
                        <input type="text" name="cb_map_options[marker_item_draft_icon_height]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'marker_item_draft_icon_height')); ?>" size="3"> px
                    </td>
                </tr>
                <tr id="marker-item-draft-icon-anchor" class="display-none">
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'anchor point' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the position of the anchor point of the icon image, seen from the left top corner of the icon, often it is half of the width and full height of the icon size - this point is used to place the marker on the geo coordinates' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[marker_item_draft_icon_anchor_x]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'marker_item_draft_icon_anchor_x')); ?>" size="3"> x
                        <input type="text" name="cb_map_options[marker_item_draft_icon_anchor_y]"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'marker_item_draft_icon_anchor_y')); ?>" size="3"> px
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-filter-users">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Filter for Users' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'show location distance filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the location distance filter' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[show_location_distance_filter]" <?php echo  MapAdmin::get_option($cb_map_id, 'show_location_distance_filter') ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'label for location distance filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('alternative label for the location distance filter' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[label_location_distance_filter]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'distance' ,'commonsbooking')); ?>"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'label_location_distance_filter') ); ?>"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'address search bounds - left bottom' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the bottom left corner of the address search bounds' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[address_search_bounds_left_bottom_lon]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'longitude' ,'commonsbooking')); ?>"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'address_search_bounds_left_bottom_lon')); ?>" size="7"> /
                        <input type="text" name="cb_map_options[address_search_bounds_left_bottom_lat]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'latitude' ,'commonsbooking')); ?>"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'address_search_bounds_left_bottom_lat')); ?>" size="7">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'address search bounds - right top' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the top right corner of the address search bounds' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <input type="text" name="cb_map_options[address_search_bounds_right_top_lon]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'longitude' ,'commonsbooking')); ?>"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'address_search_bounds_right_top_lon')); ?>" size="7"> /
                        <input type="text" name="cb_map_options[address_search_bounds_right_top_lat]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'latitude' ,'commonsbooking')); ?>"
                               value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id,
                                   'address_search_bounds_right_top_lat')); ?>" size="7">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'show item availability filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('activate to show the item availability filter' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="checkbox"
                               name="cb_map_options[show_item_availability_filter]" <?php echo  MapAdmin::get_option($cb_map_id,'show_item_availability_filter') ? 'checked="checked"' : '' ?> value="on"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'label for item availability filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('alternative label for the item availability filter' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[label_item_availability_filter]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'availability' ,'commonsbooking')); ?>"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'label_item_availability_filter') ); ?>"></td>
                </tr>
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'label for item category filter' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('alternative label for the item category filter' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text" name="cb_map_options[label_item_category_filter]"
                               placeholder="<?php echo commonsbooking_sanitizeHTML( __( 'categories' ,'commonsbooking')); ?>"
                               value="<?php echo  commonsbooking_sanitizeHTML( MapAdmin::get_option($cb_map_id, 'label_item_category_filter') ); ?>"></td>
                </tr>

                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __(     'custom text for filter button' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('the text for the button used for filtering' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td><input type="text"
                                name="cb_map_options[custom_filterbutton_label]" value="<?php echo esc_attr(MapAdmin::get_option($cb_map_id, 'custom_filterbutton_label')); ?>"></td>
                </tr>

                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'available categories' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('select the categories that are presented the users to filter items - none for no filters' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <ul class="cb-map-settings-cat-filter-list">
                            <div class="category-wrapper">
                                <?php echo  commonsbooking_sanitizeHTML( $available_categories_checklist_markup ) ?>
                            </div>
                        </ul>
                    </td>
                </tr>
            </table>

            <table class="text-left" id="available-categories-custom-markup-wrapper">
                <tr>
                    <th><?php echo commonsbooking_sanitizeHTML( __(     'grouping of and custom markup for filters' ,'commonsbooking')); ?></th>
                    <td>
                        <button id="add-filter-group-button" class="button"
                                title="<?php echo commonsbooking_sanitizeHTML( __('add filter group' ,'commonsbooking')); ?>"><span class="dashicons dashicons-plus"></span></button>
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-filter-presets">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Filter Item Presets' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'preset categories' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('select the categories that are used to prefilter the items that are shown on the map - none for all items' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <ul class="cb-map-settings-cat-filter-list">
                            <div class="category-wrapper">
                                <?php echo  commonsbooking_sanitizeHTML( $preset_categories_checklist_markup ) ?>
                            </div>
                        </ul>
                    </td>
                </tr>
            </table>
        </details>
    </div>

    <div class="option-group" id="option-group-filter-location-presets">
        <details>
            <summary><?php echo commonsbooking_sanitizeHTML( __( 'Filter Location Presets' ,'commonsbooking')); ?></summary>
            <table class="text-left">
                <tr>
                    <th>
                        <?php echo commonsbooking_sanitizeHTML( __( 'preset categories' ,'commonsbooking')); ?>:
                        <span class="dashicons dashicons-editor-help"
                              title="<?php echo commonsbooking_sanitizeHTML( __('select the categories that are used to prefilter the location categories that are shown on the map - none for all locations' ,'commonsbooking')); ?>"></span>
                    </th>
                    <td>
                        <ul class="cb-map-settings-cat-filter-list">
                            <div class="category-wrapper">
                                <?php echo  commonsbooking_sanitizeHTML( $preset_location_categories_checklist_markup ) ?>
                            </div>
                        </ul>
                    </td>
                </tr>
            </table>
        </details>
    </div>

</div>

<script>

    jQuery(document).ready(function ($) {


        //show options inside option groups
        $('.option').show();

        //show/hide groups
        $('.option-group').each(function () {
            var $this = $(this);
            var option_group_name = $this.attr('id').replace('option-group-', '');

            $(this).show();

            //show options
            $('input').show();
            $('textarea').show();
        });

        //----------------------------------------------------------------------------
        // grouping & custom markup of user filters

        $('.cb_items_available_category_choice').change(function () {
            var $this = $(this);
            var el_id_arr = $this.attr('id').split('-');
            var cat_id = el_id_arr[el_id_arr.length - 1];
            //console.log(cat_id);

            if ($this.prop("checked")) {
                //console.log('checked');
                add_custom_markup_option(cat_id, $this.parent().text(), $this.parent().text().trim());
            } else {
                //console.log('unchecked');
                $('#available_category_cutom_markup_' + cat_id).remove();
            }

        });

        function add_filter_group(group_id, group_name) {
            var $accm_table = $('#available-categories-custom-markup-wrapper');
            group_id = group_id ? group_id : 'g' + new Date().getTime() + '-' + Math.floor(Math.random() * 1000000);
            group_name = group_name ? group_name : '';
            var $row = $('<tr><th><?php echo commonsbooking_sanitizeHTML( __(     'filter group' ,'commonsbooking')); ?>:</th><td><input style="width: 250px;" type="text" placeholder="<?php echo commonsbooking_sanitizeHTML( __(     'group name' ,'commonsbooking')); ?>" name="cb_map_options[cb_items_available_categories][' + group_id + ']" value="' + group_name + '"></td></tr>');
            $accm_table.append($row);
            if (!$row.is(':nth-child(2)')) {
                var $group_remove_button = $('<button style="margin-left: 10px;" class="button" title="<?php echo commonsbooking_sanitizeHTML( __( 'remove filter group' ,'commonsbooking')); ?>"><span class="dashicons dashicons-trash"></span></button>');

                $($group_remove_button).click(function (event) {
                    event.preventDefault();

                    $(this).parent('tr').remove();
                });

                $row.append($group_remove_button);
            }
        }

        function add_custom_markup_option(cat_id, label_text, markup) {
            var $accm_table = $('#available-categories-custom-markup-wrapper');
            var $row = $('<tr id="available_category_cutom_markup_' + cat_id + '"><th class="filter-label-name">' + label_text + ':</th><td><textarea style="width: 250px;" name="cb_map_options[cb_items_available_categories][' + cat_id + ']">' + markup + '</textarea></td></tr>');
            $accm_table.append($row);
        }

        function add_custom_markup_options() {
            var custom_markup_options_data = <?php echo  wp_json_encode($available_categories); ?>;

            if (custom_markup_options_data.length > 0) {
                $.each(custom_markup_options_data, function (index, item) {
                    if (item.id.substring(0, 1) == 'g' ,'commonsbooking') {
                        add_filter_group(item.id, item.content);
                    } else {
                        var $cat_choice = $(".cb_items_available_category_choice[value='" + item.id + "']");
                        var markup = custom_markup_options_data[item.id] || $cat_choice.parent().text().trim();
                        add_custom_markup_option(item.id, $cat_choice.parent().text(), item.content);
                    }
                });
            } else {
                add_filter_group();
            }

        }

        add_custom_markup_options();

        $('#add-filter-group-button').click(function (event) {
            event.preventDefault();
            add_filter_group();
        });

    });

</script>
