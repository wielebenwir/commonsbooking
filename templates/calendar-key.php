<?php

/**
 * Template: calendar-key
 *
 * This template part is used by timeframe-calendar and the item table
 */

?>
<div id="cb-colorkey-legend">
	<strong><?php echo commonsbooking_sanitizeHTML( __( 'Color legend', 'commonsbooking' ) ); ?>:</strong><br>
	<div class="colorkey-square colorkey-accept"></div> <?php echo commonsbooking_sanitizeHTML( __( 'bookable', 'commonsbooking' ) ); ?> | 
	<div class="colorkey-square colorkey-cancel"></div> <?php echo commonsbooking_sanitizeHTML( __( 'booked/blocked', 'commonsbooking' ) ); ?>  | 
	<div class="colorkey-square colorkey-holiday"></div> <?php echo commonsbooking_sanitizeHTML( __( 'station closed', 'commonsbooking' ) ); ?>  | 
	<div class="colorkey-square colorkey-greyedout"></div> <?php echo commonsbooking_sanitizeHTML( __( 'not bookable', 'commonsbooking' ) ); ?> <br>
</div>