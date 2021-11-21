<?php
/**
 * Shortcode [cb_bookings]
 * Model: Booking
 * View: Booking::shortcode
 * $templateDatais set in Model/View/getBookingListData()
 *
 * List all allowed bookings with filter
 * 
 *
 */

if ( !is_user_logged_in() )  {
    $noResultText = sprintf( commonsbooking_sanitizeHTML(  __('Please <a href="%s"> login in </a> to see your bookings.', 'commonsbooking') ), wp_login_url() );
} else { 
    $noResultText = commonsbooking_sanitizeHTML(  __("No bookings available.", "commonsbooking") );
}

$response = '';

if ($templateData && $templateData['total'] > 0) {

    $showFilters = !commonsbooking_isCurrentUserSubscriber();


    foreach ($templateData as $label => $values) {

        var_dump($label);
    }


    // Remove line breaks and whitespaces between tags
    $response = preg_replace( "/\r|\n/", "", $response);
    $response = preg_replace('/\>\s+\</m', '><', $response);
    echo $response;

} else {
    echo $noResultText;
}
