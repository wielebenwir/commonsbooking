<?php

namespace CommonsBooking\Exception;

use CommonsBooking\Model\Booking;

class TimeframeInvalidException extends \Exception {

	public function __construct( $message = '', $code = 0, \Throwable $previous = null ) {
		// get immediate caller
		if ( debug_backtrace()[1]['class'] == Booking::class ) {
			$message .= ' ' . __( 'Booking is saved as draft.', 'commonsbooking' );
		} else {
			$message .= ' ' . __( 'Timeframe is saved as draft.', 'commonsbooking' );
		}
		parent::__construct( $message, $code, $previous );
	}
}
