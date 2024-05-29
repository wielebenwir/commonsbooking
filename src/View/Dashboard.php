<?php

namespace CommonsBooking\View;

/**
 * The dashboard that can be seen in the WordPress Backend under CommonsBooking
 */
class Dashboard extends View {

	public static function index() {
		ob_start();
		commonsbooking_sanitizeHTML( commonsbooking_get_template_part( 'dashboard', 'index' ) );
		echo ob_get_clean();
	}

	/**
	 * Renders list of beginngin bookings for today.
	 * @return void
	 * @throws \Exception
	 */
	public static function renderBeginningBookings() {
		$beginningBookings = \CommonsBooking\Repository\Booking::getBeginningBookingsByDate( time() );

		// filter bookings to show only allowed bookings for current user role
		if ( $beginningBookings ) {
			$beginningBookings = array_filter( $beginningBookings, function ( $beginningBooking ) {
				return commonsbooking_isCurrentUserAllowedToEdit( $beginningBooking );
			} );
		}

		if ( count( $beginningBookings ) > 0 ) {
			usort( $beginningBookings, function ( $a, $b ) {
				return strtotime( $a->getStartTime() ) <=> strtotime( $b->getStartTime() );
			} );
			$html = '<div style="padding:5px 20px 5px 20px">';
			$html .= '<ul>';
			/** @var \CommonsBooking\Model\Booking $booking */
			foreach ( $beginningBookings as $booking ) {
				$html .= '<li>';
				$html .=  '<strong>' . $booking->pickupDatetime() . ' </strong> => ' . $booking->returnDatetime() . "<br>";
				$html .=  '<a href="'. $booking->bookingLinkUrl() . '" target="_blank">' . $booking->getItem()->title() . ' ' . __( 'at', 'commonsbooking' ) . ' ' . $booking->getLocation()->title() . '</a>';
				$html .=  '</li>';
				$html .= '<hr style="border-top: 1px solid #bbb; border-radius: 0px; border-color:#67b32a;">';
			}
			$html .= '</ul>';
			$html .= '</div>';
			return $html;
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

		// filter bookings to show only allowed bookings for current user role
		if ( $endingBookings ) {
			$endingBookings = array_filter( $endingBookings, function ( $endingBooking ) {
				return commonsbooking_isCurrentUserAllowedToEdit( $endingBooking );
			} );
		}

		if ( count( $endingBookings ) ) {
			usort( $endingBookings, function ( $a, $b ) {
				return strtotime( $a->getEndTime() ) <=> strtotime( $b->getEndTime() );
			} );
			//return self::renderBookingsTable( $endingBookings, false);
			$html = '<div style="padding:5px 20px 5px 20px">';
			$html .= '<ul>';
			/** @var \CommonsBooking\Model\Booking $booking */
			foreach ( $endingBookings as $booking ) {
				$html .= '<li>';
				$html .=  '<strong>' . $booking->returnDatetime() . "</strong><br>";
				$html .=  '<a href="'. $booking->bookingLinkUrl() . '" target="_blank">' . $booking->getItem()->title() . ' ' . __( 'at', 'commonsbooking' ) . ' ' . $booking->getLocation()->title() . '</a>';
				$html .=  '</li>';
				$html .= '<hr style="border-top: 1px solid #bbb; border-radius: 0px; border-color:#67b32a;">';
			}
			$html .= '</ul>';
			$html .= '</div>';
	
			return $html;
		} else {
			return false;
		}
	}

}
