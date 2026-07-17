<?php
	/**
	 * Single item calendar with booking functionality
	 *
	 * Used on item single
	 */
	global $templateData;
	$templateData     = \CommonsBooking\View\Item::getTemplateData();
	$noResultText     = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'item-not-available' );
	$bookThisItemText = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'text_book-this-item' );

	do_action( 'commonsbooking_before_item-single', $templateData['item']->ID, $templateData['item'] );

	// Single Item View
if ( array_key_exists( 'location', $templateData ) && $templateData['location'] ) { // item selected, so we display the booking calendar
	echo '<h2>' . esc_html__( $bookThisItemText, 'commonsbooking' ) . '</h2>';
	commonsbooking_get_template_part( 'location', 'calendar-header' ); // file: item-calendar-header.php
	commonsbooking_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php
}

	// Multi item view
if ( array_key_exists( 'locations', $templateData ) && $templateData['locations'] ) {
	foreach ( $templateData['locations'] as $location ) {
		$templateData['location'] = $location;
		commonsbooking_get_template_part( 'item', 'withlocation' ); // file: location-withitem.php
	}  // end foreach $timeframes
} // $item_is_selected


	// item not available if no valid location reference found
if ( ! array_key_exists( 'location', $templateData ) && empty( $templateData['locations'] ) ) { ?>
		<div class="cb-status cb-availability-status cb-status-not-available">
		<?php
		echo commonsbooking_sanitizeHTML( $noResultText );
}

if ( ! is_user_logged_in() ) {
	$current_url = $_SERVER['REQUEST_URI'];
	?>
		<div class="cb-notice">
	<?php
		printf(
			/* translators: %1$s: wp_login_url, 1$s: wp_registration_url */
			commonsbooking_sanitizeHTML( __( 'To be able to book, you must first <a href="%1$s">login</a> or <a href="%2$s">register</a>.', 'commonsbooking' ) ),
			esc_url( wp_login_url( $current_url ) ),
			esc_url( wp_registration_url() )
		);
	?>
		</div>
	<?php
}

do_action( 'commonsbooking_after_item-single', $templateData['item']->ID, $templateData['item'] );