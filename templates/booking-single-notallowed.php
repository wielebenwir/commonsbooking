<?php
/**
 * Booking Single not allowed (Not admin or author of post)
 */

?>

<div class="cb-notice error">
	<?php
	echo esc_html__( 'You are not allowed to access this booking.', 'commonsbooking' ) . '<br>';
	if ( ! is_user_logged_in() ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( wp_login_url() ),
			esc_html__( 'Login to your account', 'commonsbooking' )
		);
	}
	?>
</div><!-- .cb-notice -->



