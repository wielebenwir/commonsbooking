<?php
/**
 * Shortcode [cb_bookings]
 * Model: Booking
 * View: Booking::shortcode
 * $templateData is set in
 *
 * @see \CommonsBooking\View\Booking::getBookingListData()
 *
 * List all allowed bookings with filter
 */

global $templateData;
if ( ! is_user_logged_in() ) {
	$current_url  = $_SERVER['REQUEST_URI'];
	$noResultText = sprintf( commonsbooking_sanitizeHTML( __( 'Please <a href="%s"> login </a> to see your bookings.', 'commonsbooking' ) ), wp_login_url( $current_url ) );
} else {
	$noResultText = commonsbooking_sanitizeHTML( __( 'No bookings available.', 'commonsbooking' ) );
}

$response = '';

if ( $templateData && $templateData['total'] > 0 ) {
	$showFilters = ! commonsbooking_isCurrentUserSubscriber();

	$response .= '
	<div class="booking-list">';

	$response .= $templateData['menu'] ?? '';

	$response .= '
        <div class="booking-list--filters cb-filter' . ( $showFilters ?: ' hide' ) . '">
        	<div class="filter-wrapper">
	            <label class="filter-label">' . __( 'Startdate', 'commonsbooking' ) . '</label>
	             <div class="filter-startdate" id="filter-startdate">        
	                <input id="startDate-datepicker" type="text" value="">
	                <input id="startDate" type="hidden" value="">
	            </div>
        	</div>
        	<div class="filter-wrapper">
	            <label class="filter-label">' . __( 'Enddate', 'commonsbooking' ) . '</label>
	            <div class="filter-enddate" id="filter-enddate">
	                <input id="endDate-datepicker" type="text" value="">
	                <input id="endDate" type="hidden" value="">
	            </div>
	        </div>';

	foreach ( $templateData['filters'] as $label => $values ) {
		$response .= '
            <div class="filter-wrapper">
                <label class="filter-label">' . __( ucfirst( $label ), 'commonsbooking' ) . '</label>
                <div class="filter-' . $label . 's">
                    <select class="select2" id="filter-' . $label . '">
                        <option value="all" selected="selected">' . __( 'All', 'commonsbooking' ) . '</option>
            ';
		foreach ( $values as $value ) {
			$response .= sprintf( '<option value="%s">%s</option>', $value, __( $value, 'commonsbooking' ) );
		}

		$response .= '
					</select>
	            </div>
	        </div>';
	}

	$response .= '
	        <div class="filter-wrapper">
	            <label class="filter-label">' . __( 'Sorting', 'commonsbooking' ) . '</label>
	            <select class="select2" id="sorting">
	                <option value="startDate">' . __( 'Startdate', 'commonsbooking' ) . '</option>
	                <option value="endDate">' . __( 'Enddate', 'commonsbooking' ) . '</option>
	                <option value="item">' . __( 'Item', 'commonsbooking' ) . '</option>
	                <option value="user">' . __( 'User', 'commonsbooking' ) . '</option>
	                <option value="location">' . __( 'Location', 'commonsbooking' ) . '</option>
	            </select>
	        </div>
	        <div class="filter-wrapper">
	            <label class="filter-label">' . __( 'Order', 'commonsbooking' ) . '</label>
	            <select class="select2" id="order">
	                <option value="asc">' . __( 'Ascending', 'commonsbooking' ) . '</option>
	                <option value="desc">' . __( 'Descending', 'commonsbooking' ) . '</option>
	            </select>
	        </div>
	        <div class="filter-wrapper reset-filters">
	            <a id="reset-filters">' . __( 'Reset filters', 'commonsbooking' ) . '</a>
	            <a class="cb-button" id="filter">' . __( 'Filter', 'commonsbooking' ) . '</a>
	        </div>
	    </div>';

	$response .= '
        <div id="booking-list--results">
            <div class="my-sizer-element"></div>
        </div>
        <div id="booking-list--pagination" style="display: none"></div>
    </div>
    ';

	// Remove line breaks and whitespaces between tags
	$response = preg_replace( "/\r|\n/", '', $response );
	$response = preg_replace( '/\>\s+\</m', '><', $response );
	echo commonsbooking_sanitizeHTML( $response );
} else {
	echo commonsbooking_sanitizeHTML( $noResultText );
}
