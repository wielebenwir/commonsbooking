<?php
/**
 * This template is called in the method \CommonsBooking\Model\Booking\booking_action_buttons($form_action)
 */

if ($current_status == 'unconfirmed' and $form_action == "cancel") {
    $form_post_status = 'canceled';
    $button_label = esc_html__('Cancel', 'commonsbooking');
}

if ($current_status == 'unconfirmed' and $form_action == "confirm") {
    $form_post_status = 'confirmed';
    $button_label = esc_html__('Confirm Booking', 'commonsbooking');
}

if ($current_status == 'confirmed' and $form_action == "cancel") {
    $form_post_status = 'canceled';
    $button_label = esc_html__('Cancel Booking', 'commonsbooking');
}

if(isset($form_post_status)) {
?>
    <form method="post" id="cb-booking-form-set-<?php echo $form_post_status; ?>">
        <?php echo \CommonsBooking\Wordpress\CustomPostType\Booking::getWPNonceField() ?>
        <?php if ($booking->ID) { ?><input type="hidden" name="post_ID" value="<?php echo $booking->ID; ?>" /><?php } ?>
        <input type="hidden" name="location-id" value="<?php echo $booking->getLocation()->ID; ?>"/>
        <input type="hidden" name="item-id" value="<?php echo $booking->getItem()->ID; ?>"/>
        <input type="hidden" name="type" value="<?php echo $booking->getMeta('type'); ?>"/>
        <input type="hidden" name="post_type" value="<?php echo $booking->post_type; ?>"/>
        <?php if ($form_post_status !== 'canceled') { ?>
            <input type="hidden" name="comment" value="<?php echo $booking->getMeta('comment'); ?>"/>
        <?php } ?>
        <input type="hidden" name="post_status" value="<?php echo $form_post_status; ?>"/>
        <input type="hidden" name="repetition-start" value="<?php echo $booking->getMeta('repetition-start'); ?>">
        <input type="hidden" name="repetition-end" value="<?php echo $booking->getMeta('repetition-end'); ?>">
        <input type="submit" value="<?php echo $button_label; ?>" class="<?php echo "cb-action-" . $form_post_status; ?>"/>
    </form>
<?php
}
?>