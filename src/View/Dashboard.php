<?php

namespace CommonsBooking\View;

class Dashboard extends View {

	public static function index() {
		ob_start();
		commonsbooking_get_template_part( 'dashboard', 'index' );
		echo ob_get_clean();
	}

	/**
	 * Renders list of beginngin bookings for today.
	 * @return void
	 * @throws \Exception
	 */
	public static function renderBeginningBookings() {
		$beginningBookings = \CommonsBooking\Repository\Booking::getBeginningBookingsByDate( time() );
		if ( count( $beginningBookings ) > 0 ) {
			usort( $beginningBookings, function ( $a, $b ) {
				return strtotime( $a->getStartTime() ) > strtotime( $b->getStartTime() );
			} );
			return self::renderBookingsTable( $beginningBookings );
		} else {
			return false;
		}
	}

		
	/**
	 * Renders list of ending bookings for today.
	 * @return void
	 * @throws \Exception
	 */
	public static function renderEndingBookings() {
		$endingBookings = \CommonsBooking\Repository\Booking::getEndingBookingsByDate( time() );
		if ( count( $endingBookings ) ) {
			usort( $endingBookings, function ( $a, $b ) {
				return strtotime( $a->getEndTime() ) > strtotime( $b->getEndTime() );
			} );
			return self::renderBookingsTable( $endingBookings, false);
		} else {
			return false;
		}
	}

	/**
	 * Renders list of bookings.
	 * @param $bookings
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected static function renderBookingsTable( $bookings, $showStarttime = true) {
		$html = '<div style="padding:5px 20px 5px 20px">';
		$html .= '<ul>';
		/** @var \CommonsBooking\Model\Booking $booking */
		foreach ( $bookings as $booking ) {
			$html .= '<li>';
			$html .=  '<strong>' . $booking->pickupDatetime() . ' </strong> => ' . $booking->returnDatetime() . "<br>";
			$html .=  $booking->getItem()->title() . ' ' . __( 'at', 'commonsbooking' ) . ' ' . $booking->getLocation()->title();
			$html .=  '</li>';
			$html .= '<hr style="border-top: 1px solid #bbb; border-radius: 0px; border-color:#67b32a;">';
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

}
