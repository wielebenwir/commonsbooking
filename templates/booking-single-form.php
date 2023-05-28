<?php
/**
 * This template is called in the method \CommonsBooking\Model\Booking\booking_action_buttons($form_action)
 */

if ( $current_status === 'unconfirmed' && $form_action === 'cancel' ) {
    $form_post_status = 'canceled';
    $button_label     = esc_html__( 'Cancel', 'commonsbooking' );
}

if ( $current_status === 'unconfirmed' && $form_action === 'confirm' ) {
    $form_post_status = 'confirmed';
    $button_label     = esc_html__( 'Confirm Booking', 'commonsbooking' );
}

if ( $current_status === 'confirmed' && $form_action === 'cancel' && ! $booking->isPast() ) {
    $form_post_status = 'canceled';
    $button_label     = esc_html__( 'Cancel Booking', 'commonsbooking' );
}

if ( isset( $form_post_status ) ) {
    ?>
       <?php
        if ( $booking->ID ) {
        ?>
     <form method="post" id="cb-booking-form-set-<?php echo esc_attr( $form_post_status ); ?>">
     <?php
        wp_nonce_field(
		    \CommonsBooking\Wordpress\CustomPostType\Booking::getWPAction(),
		    \CommonsBooking\Wordpress\CustomPostType\Booking::getWPNonceId(),
		    false,
		    true
	    );
        ?>
        <input type="hidden" name="post_ID" value="<?php echo esc_attr( $booking->ID ); ?>" /><?php } ?>
        <input type="hidden" name="location-id" value="<?php echo esc_attr( $booking->getLocation()->ID ); ?>"/>
        <input type="hidden" name="item-id" value="<?php echo esc_attr( $booking->getItem()->ID ); ?>"/>
        <input type="hidden" name="type" value="<?php echo esc_attr( $booking->getMeta( 'type' ) ); ?>"/>
        <input type="hidden" name="post_type" value="<?php echo esc_attr( $booking->post_type ); ?>"/>
        <?php if ( $form_post_status !== 'canceled' ) { ?>
            <input type="hidden" name="comment" value="<?php echo esc_attr( $booking->getMeta( 'comment' ) ); ?>"/>
        <?php } ?>
        <input type="hidden" name="post_status" value="<?php echo esc_attr( $form_post_status ); ?>"/>
        <input type="hidden" name="repetition-start" value="<?php echo esc_attr( $booking->getMeta( 'repetition-start' ) ); ?>">
        <input type="hidden" name="repetition-end" value="<?php echo esc_attr( $booking->getMeta( 'repetition-end' ) ); ?>">
        <input type="submit" value="<?php echo esc_attr( $button_label ); ?>" class="<?php echo 'cb-action-' . commonsbooking_sanitizeHTML( $form_post_status ); ?>"/>	
	</form>
</li>
    </form>
    <?php
}
?>
