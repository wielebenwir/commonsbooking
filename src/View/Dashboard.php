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
		if ( count( $beginningBookings ) ) {
			self::renderBookingsTable( $beginningBookings );
		}
	}

	/**
	 * Renders list of bookings.
	 * @param $bookings
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected static function renderBookingsTable( $bookings ) {
		usort( $bookings, function ( $a, $b ) {
			return strtotime( $a->getStartTime() ) > strtotime( $b->getStartTime() );
		} );

		echo '<ul>';
		/** @var \CommonsBooking\Model\Booking $booking */
		foreach ( $bookings as $booking ) {
			echo '<li>';
			echo '[' . $booking->getStartTime() . '] ' .
			     $booking->getItem()->title() . ' ' . __( 'in', 'commonsbooking' ) . ' ' .
			     $booking->getLocation()->title() .
			     ' ' . $booking->formattedBookingDate() .
			     ' ' . __( 'at', 'commonsbooking' );

			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Renders list of ending bookings for today.
	 * @return void
	 * @throws \Exception
	 */
	public static function renderEndingBookings() {
		$endingBookings = \CommonsBooking\Repository\Booking::getEndingBookingsByDate( time() );
		if ( count( $endingBookings ) ) {
			self::renderBookingsTable( $endingBookings );
		}
	}

}
