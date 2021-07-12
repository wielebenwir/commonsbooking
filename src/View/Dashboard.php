<?php

namespace CommonsBooking\View;

class Dashboard extends View {

	public static function index() {
		ob_start();
		commonsbooking_get_template_part( 'dashboard', 'index' );
		echo ob_get_clean();
	}

	public static function content( \WP_Post $post ) {
		// TODO: Implement content() method.
	}
}
