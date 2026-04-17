<?php
	global $templateData;
	$location = $templateData['location'];

	do_action( 'commonsbooking_before_location-calendar-header', $location->ID, $location );

	echo commonsbooking_sanitizeHTML( $location->thumbnail( 'cb_listing_small' ) ); // div.thumbnail is printed by function
?>
<div class="cb-list-info">
	<h4 class="cb-title cb-location-title">
		<a href=" <?php echo commonsbooking_sanitizeHTML( get_permalink( $location->ID ) ); ?> ">
			<?php echo commonsbooking_sanitizeHTML( $location->post_title ); ?>
		</a>
	</h4>
	<?php
	$locationAddress = $location->formattedAddressOneLine();
	if ( ! empty( $locationAddress ) ) {
		?>
		<div class="cb-address cb-location-address"><?php echo commonsbooking_sanitizeHTML( $locationAddress ); ?></div>
		<?php
	}
	?>
	<?php
	if ( $location->hasMap() ) {
		\CommonsBooking\View\Location::renderLocationMap( $location );
	}
	?>
		<div class="cb-address cb-location-pickupinstructions">
		<?php
		// if pickup instructions are set in location meta
		if ( $location->formattedPickupInstructionsOneLine() ) {
			?>
		<strong><?php echo esc_html__( 'Pickup instructions:', 'commonsbooking' ); ?></strong>
			<?php echo commonsbooking_sanitizeHTML( $location->formattedPickupInstructionsOneLine() ); ?>
			<?php
		} // end if pickup instructions
		?>
	</div>
</div>

<?php

do_action( 'commonsbooking_after_location-calendar-header', $location->ID, $location );

?>
