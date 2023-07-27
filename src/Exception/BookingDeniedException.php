<?php

namespace CommonsBooking\Exception;

class BookingDeniedException extends \Exception
{
	private string $redirectUrl;

	/**
	 * @param string $message - The message to show to the user
	 * @param string $redirectUrl - The url where the user should be redirected to after the message is shown
	 */
	public function __construct( string $message = "", string $redirectUrl = "") {
		parent::__construct( $message);
		$this->redirectUrl = $redirectUrl;
	}

	/**
	 * Will get the URL to redirect the user to after a booking is denied.
	 * It is the referrer by default
	 * @return string
	 */
	public function getRedirectUrl(): string {
		if ($this->redirectUrl) {
			return $this->redirectUrl;
		}
		else {
			return sanitize_url( wp_get_referer() );
		}
	}

}