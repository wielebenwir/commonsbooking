<?php
/**
 * Timeframe types other than bookings are not allowed for preview in frontend
 */

?>

<div class="cb-notice error">
	<?php
	echo esc_html__( 'It is not possible to access this timeframe on frontend. Please edit timeframe in backend. If you want to check the result of your timeframe settings visit the item or location in frontend to see the booking calender', 'commonsbooking' ) . '<br>';
	if ( ! is_user_logged_in() ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( wp_login_url() ),
			esc_html__( 'Login to your account', 'commonsbooking' )
		);
	}
	?>
</div><!-- .cb-notice -->



