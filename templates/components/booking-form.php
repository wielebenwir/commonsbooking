<form method="post">
        <?php echo \CommonsBooking\Wordpress\CustomPostType\Timeframe::getWPNonceField() ?>
<?php if ($booking->ID) { ?><input type="hidden" name="post_ID" value="<?php echo $booking->ID; ?>" /><?php } ?>
        <input type="hidden" name="location-id" value="<?php echo $booking->getLocation()->ID; ?>" />
        <input type="hidden" name="item-id" value="<?php echo $booking->getItem()->ID; ?>" />
        <input type="hidden" name="type" value="<?php echo $booking->get_meta('type'); ?>" />
        <input type="hidden" name="post_type" value="cb_timeframe" />
        <input type="hidden" name="post_status" value="confirmed" />
        <input type="hidden" name="start-date" value="<?php echo $booking->get_meta('start-date'); ?>">
        <input type="hidden" name="end-date" value="<?php echo $booking->get_meta('start-date'); ?>">
        <input type="submit" value="<?php echo $booking->submitLabel(); ?>" />
    </form>

<form method="post">
    <?php echo \CommonsBooking\Wordpress\CustomPostType\Timeframe::getWPNonceField() ?>
<?php if ($booking->ID) { ?><input type="hidden" name="post_ID" value="<?php echo $booking->ID; ?>" /><?php } ?>
        <input type="hidden" name="location-id" value="<?php echo $booking->getLocation()->ID; ?>" />
        <input type="hidden" name="item-id" value="<?php echo $booking->getItem()->ID; ?>" />
        <input type="hidden" name="type" value="<?php echo $booking->get_meta('type'); ?>" />
        <input type="hidden" name="post_type" value="cb_timeframe" />
        <input type="hidden" name="post_status" value="cancelled" />
        <input type="hidden" name="start-date" value="<?php echo $booking->get_meta('start-date'); ?>">
        <input type="hidden" name="end-date" value="<?php echo $booking->get_meta('start-date'); ?>">
        <input type="submit" value="<?php echo $booking->cancelLabel(); ?>" />
    </form>

