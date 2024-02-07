<?php

namespace CommonsBooking\Exception;

class RestrictionInvalidException extends \Exception
{
	public function __construct( $message  ) {
		parent::__construct( __('Invalid restriction settings: ','commonsbooking') . $message . __( ' Restriction is saved as draft.', 'commonsbooking' ) );
	}
}