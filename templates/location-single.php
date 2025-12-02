<?php
	/**
	 * Single location
	 *
	 * List timeframes (if multiple) or show the booking calendar
	 */
	global $templateData;
	$templateData     = \CommonsBooking\View\Location::getTemplateData();
	$noResultText     = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'location-without-items' );
	$bookThisItemText = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'text_book-this-item' );

	do_action( 'commonsbooking_before_location-single', $templateData['location']->ID, $templateData['location'] );

	commonsbooking_get_template_part( 'location', 'single-meta' ); // file: location-single-meta.php

	// Single Item View
if ( array_key_exists( 'item', $templateData ) && $templateData['item'] ) { // item selected, so we display the booking calendar
	echo '<h2>' . esc_html__( $bookThisItemText, 'commonsbooking' ) . '</h2>';
	commonsbooking_get_template_part( 'item', 'calendar-header' ); // file: item-calendar-header.php
	commonsbooking_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php
}

	// Multi item view
if ( array_key_exists( 'items', $templateData ) && $templateData['items'] ) {
	foreach ( $templateData['items'] as $item ) {
		$templateData['item'] = $item;
		commonsbooking_get_template_part( 'location', 'withitem' ); // file: location-withitem.php
	}  // end foreach $timeframes
} // $item_is_selected

if ( ! array_key_exists( 'item', $templateData ) && ! array_key_exists( 'items', $templateData ) ) { ?>
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
do_action( 'commonsbooking_after_location-single', $templateData['location']->ID, $templateData['location'] );