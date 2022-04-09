<?php
/**
 * Shortcode [cb_statistics-user]
 * 
 *
 * Show statistics for current user or users managed by current admin
 *
 * Statistics are rendered in View/Statistics
 * 
 *
 */
global $templateData;
if ( !is_user_logged_in() )  {
	$current_url = $_SERVER['REQUEST_URI'];
    $noResultText = sprintf( commonsbooking_sanitizeHTML(  __('Please <a href="%s">login</a> to see the booking statistics.', 'commonsbooking') ), wp_login_url( $current_url ) );
} else { 
    $noResultText = commonsbooking_sanitizeHTML(  __("No bookings available.", "commonsbooking") );
}

$response = '';

if ($templateData && $templateData['total'] > 0) {
   
    #$response = "Hallo Statistics Shortcode!";
    echo commonsbooking_sanitizeHTML($response);

} else {
    echo commonsbooking_sanitizeHTML($noResultText);
}
?>