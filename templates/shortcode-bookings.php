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
	            <label for="startDate-datepicker" class="filter-label">' . __( 'Startdate', 'commonsbooking' ) . '</label>
	             <div class="filter-startdate" id="filter-startdate">
	                <input name="startDate-datepicker" id="startDate-datepicker" type="text" value="">
	                <input id="startDate" type="hidden" value="">
	            </div>
        	</div>
        	<div class="filter-wrapper">
	            <label for="endDate-datepicker" class="filter-label">' . __( 'Enddate', 'commonsbooking' ) . '</label>
	            <div class="filter-enddate" id="filter-enddate">
	                <input name="endDate-datepicker" id="endDate-datepicker" type="text" value="">
	                <input id="endDate" type="hidden" value="">
	            </div>
	        </div>';

	foreach ( $templateData['filters'] as $label => $values ) {
		$response .= '
            <div class="filter-wrapper">
                <label for="filter-' . $label . '" class="filter-label">' . __( ucfirst( $label ), 'commonsbooking' ) . '</label>
                <div class="filter-' . $label . 's">
                    <select name="filter-' . $label . '" class="select2" id="filter-' . $label . '">
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
	            <label for="sorting" class="filter-label">' . __( 'Sorting', 'commonsbooking' ) . '</label>
	            <select name="sorting" class="select2" id="sorting">
	                <option value="startDate">' . __( 'Startdate', 'commonsbooking' ) . '</option>
	                <option value="endDate">' . __( 'Enddate', 'commonsbooking' ) . '</option>
	                <option value="item">' . __( 'Item', 'commonsbooking' ) . '</option>
	                <option value="user">' . __( 'User', 'commonsbooking' ) . '</option>
	                <option value="location">' . __( 'Location', 'commonsbooking' ) . '</option>
	            </select>
	        </div>
	        <div class="filter-wrapper">
	            <label for="order" class="filter-label">' . __( 'Order', 'commonsbooking' ) . '</label>
	            <select name="order" class="select2" id="order">
	                <option value="asc">' . __( 'Ascending', 'commonsbooking' ) . '</option>
	                <option value="desc">' . __( 'Descending', 'commonsbooking' ) . '</option>
	            </select>
	        </div>
	        <div class="filter-wrapper reset-filters">
	            <button type="button" id="reset-filters" class="cb-button reset-filters">
	                ' . __( 'Reset filters', 'commonsbooking' ) . '
	            </button>
	            <button type="submit" id="filter" class="cb-button">
	                ' . __( 'Filter', 'commonsbooking' ) . '
	            </button>
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
