<?php


namespace CommonsBooking\View;


class Statistics extends View {

	/**
	 * Callback function for user statistics
	 *
	 * @param $field_args
	 * @param $field
	 */
    public static function shortcodeUser( $atts ) {
		global $templateData;
		$templateData = [];
		$templateData = Booking::getBookingListData();

		ob_start();

		commonsbooking_get_template_part( 'shortcode', 'statistics-user', true, false, false );

		return ob_get_clean();
	}

}