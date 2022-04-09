<?php


namespace CommonsBooking\View;
use CommonsBooking\Repository\Booking;
use CommonsBooking\Model\Statistics\User;

class Statistics extends View {

	
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct($startDate) {

		$this->starDate = $startDate;

		$this->bookings = Booking::getForCurrentUser( 'true', $startDate );
	}

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


	/**
	 * Returns array with user based statistics
	 *
	 * @param  mixed $startDate
	 * @param  mixed $endDate
	 */
	public function getTotalBookingsCountforUser() {
		$data = array();

		foreach ($this->bookings as $booking) {

			if (!array_key_exists( $booking->post_author, $data )) {
				$data[$booking->post_author] = new User();	
			} 
			
			$data[$booking->post_author]->addBooking($booking);		
		}

		return $data;

	}

	
	public static function shortcodeLocations( $atts ) {
		global $templateData;
		$templateData = [];
		$templateData = Booking::getBookingListData();

		ob_start();

		commonsbooking_get_template_part( 'shortcode', 'statistics-location', true, false, false );

		return ob_get_clean();
	}

	public static function shortcodeItems( $atts ) {
		global $templateData;
		$templateData = [];
		$templateData = Booking::getBookingListData();

		ob_start();

		commonsbooking_get_template_part( 'shortcode', 'statistics-item', true, false, false );

		return ob_get_clean();
	}

}
