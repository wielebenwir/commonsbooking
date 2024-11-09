<?php

namespace CommonsBooking\Messages;

/**
 * This is not an e-mail message like the others, but a notice that is displayed to admins and managers in the backend.
 */
class AdminMessage {

	private string $message;

	private string $notice_type;


	/**
	 * __construct
	 *
	 * @param mixed $message message text
	 * @param mixed $notice_type admin_notice type (can be: info, warning, success, error) and corresponds to the
	 *                           css class `notice-*` in the rendered element.
	 *
	 * @return void
	 */
	public function __construct( $message, $notice_type = 'info' ) {
		$this->message     = $message;
		$this->notice_type = $notice_type;

		add_action( 'admin_notices', array( $this, 'render' ) );
	}


	/**
	 * renders an admin message
	 *
	 * @return void
	 */
	public function render() {

		echo '<div class="notice notice-' . commonsbooking_sanitizeHTML( $this->notice_type ) . ' is-dismissible">';
		echo '<p>';
		echo commonsbooking_sanitizeHTML( $this->message );
		echo '</p>';
		echo '</div>';
	}

}