<?php
/**
 * Shortcode [cb_bookings]
 * Model: Booking
 *
 * List all allowed bookings for current user
 * 
 *
 */

global $templateData;
if ( !is_user_logged_in() )  {
    $noResultText = sprintf( commonsbooking_sanitizeHTML(  __('Please <a href="%s"> login in </a> to see your bookings.', 'commonsbooking') ), wp_login_url() );
} else { 
    $noResultText = commonsbooking_sanitizeHTML(  __("No bookings available.", "commonsbooking") );
}

$response = '';

if ($templateData && $templateData['total'] > 0) {

    $response .= '
<div class="booking-list">
  <div class="booking-list--filters">
    <div class="filter-wrapper">
        <p class="filter-label">' . __('Startdate', 'commonsbooking') . '</p>
         <div class="filter-startdate" id="filter-startdate">        
            <input id="startDate-datepicker" type="text" value="">
            <input id="startDate" type="hidden" value="">
        </div>
    </div>
    <div class="filter-wrapper">
        <p class="filter-label">' . __('Enddate', 'commonsbooking') . '</p>
        <div class="filter-enddate" id="filter-enddate">
            <input id="endDate-datepicker" type="text" value="">
            <input id="endDate" type="hidden" value="">
        </div>
    </div>';

    foreach ($templateData['filters'] as $label => $values) {
        $response .=  '
            <div class="filter-wrapper">
                <p class="filter-label">' . __(ucfirst($label), 'commonsbooking') . '</p>
                <div class="filter-' . $label . 's">
                    <select class="select2" id="filter-'.$label.'">
                        <option value="all" selected="selected">'.__('All', 'commonsbooking').'</option>
            ';
        foreach ($values as $value) {
            $response .=  sprintf('<option value="%s">%s</option>', $value, $value);
        }

        $response .=  '</select>
            </div>
        </div>';
    }

    $response .=  '
            <div class="filter-wrapper">
                <p class="filter-label">' . __('Sorting', 'commonsbooking') . '</p>
                <select class="select2" id="sorting">
                    <option value="startDate">' . __('Startdate', 'commonsbooking') . '</option>
                    <option value="endDate">' . __('Enddate', 'commonsbooking') . '</option>
                    <option value="item">' . __('Item', 'commonsbooking') . '</option>
                    <option value="user">' . __('User', 'commonsbooking') . '</option>
                    <option value="location">' . __('Location', 'commonsbooking') . '</option>
                </select>
            </div>
            <div class="filter-wrapper">
                <p class="filter-label">' . __('Order', 'commonsbooking') . '</p>
                <select class="select2" id="order">
                    <option value="asc">' . __('Ascending', 'commonsbooking') . '</option>
                    <option value="desc">' . __('Descending', 'commonsbooking') . '</option>
                </select>
            </div>
            <div class="filter-wrapper reset-filters">
                <a class="cb-button" id="reset-filters">' . __('Reset filters', 'commonsbooking') . '</a>
            </div>
        </div>

        <div id="booking-list--results">
            <div class="my-sizer-element"></div>
        </div>
        <div id="booking-list--pagination" style="display: none"></div>
    ';

    // Remove line breaks and whitespaces between tags
    $response = preg_replace( "/\r|\n/", "", $response);
    $response = preg_replace('/\>\s+\</m', '><', $response);
    echo $response;

} else {
    echo $noResultText;
}
