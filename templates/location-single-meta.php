<?php
/**
 * Location meta like address & pickupinfo
 *
 * WP Post properties for location are available as $location->property
 * location Model methods are available as $location->myMethod()
 */

global $templateData;
$location = $templateData['location'];

$location_address             = $location->formattedAddressOneLine();
$location_contact             = $location->formattedContactInfoOneLine();
$pickup_instructions          = $location->formattedPickupInstructions();
$show_contactinfo_unconfirmed = \CommonsBooking\Settings\Settings::getOption( 'commonsbooking_options_templates', 'show_contactinfo_unconfirmed' );
$text_hidden_contactinfo      = \CommonsBooking\Settings\Settings::getOption( 'commonsbooking_options_templates', 'text_hidden-contactinfo' );


?>

<div class="cb-list-content cb-location-address cb-col-30-70">
	<div><?php echo esc_html__( 'Adress', 'commonsbooking' ); ?></div>
	<div><?php echo commonsbooking_sanitizeHTML( $location->formattedAddressOneLine() ); ?></div>
	<?php
	if ( $location->hasMap() ) {
		\CommonsBooking\View\Location::renderLocationMap( $location );
	}
	?>
</div>

<?php if ( $location_contact ) { ?>
<div class="cb-list-content cb-location-contact cb-col-30-70">
	<div><?php echo esc_html__( 'Location contact', 'commonsbooking' ); ?></div>
	<?php
	// show contact details only after booking if options are set to show contactinfo even on unconfirmed booking status
	if ( $show_contactinfo_unconfirmed == 'on' ) {
		?>
		<div><?php echo commonsbooking_sanitizeHTML( $location->formattedContactInfoOneLine() ); ?></div>
		<?php
		// else; show info-text to inform user to confirm booking to see contact details
	} else {
		?>
		<div><strong><?php echo commonsbooking_sanitizeHTML( $text_hidden_contactinfo ); ?></strong></div>
		<?php
		// end if booking == confirmed
	}
	?>
</div> <!-- .cb-cb-contact -->
<?php } // if ( $location_contact ) ?>

<?php if ( $pickup_instructions ) { ?>
<div class="cb-list-content cb-pickupinstructions cb-col-30-70">
		<div><?php echo esc_html__( 'Pickup instructions', 'commonsbooking' ); ?></div>
		<div><?php echo commonsbooking_sanitizeHTML( $location->formattedPickupInstructionsOneLine() ); ?></div>
	</div><!-- .cb-cb-pickupinstructions -->
<?php } // end iif ($pickup_instructions) ?>
