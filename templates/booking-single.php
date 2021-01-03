<?php

/**
 * Booking Single
 */

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;

$booking       = new \CommonsBooking\Model\Booking($post->ID);
/** @var \CommonsBooking\Model\Timeframe $timeframe */
$timeframe     = $booking->getBookableTimeFrame();
$location      = $booking->getLocation();
$item          = $booking->getItem();
?>

<?php echo $booking->bookingNotice(); ?>

<div class="cb-wrapper cb-booking-item">
    <div class="cb-list-header">
        <?php echo $item->thumbnail();?>
        <h3><?php echo esc_html__('Item', 'commonsbooking'); ?>: <?php echo $item->title(); ?></h3>
        <?php echo $item->excerpt();?>
    </div>
</div>

<div class="cb-wrapper cb-booking-datetime">
    <div class="cb-list-header cb-col-30-70 cb-datetime">
        <div><?php echo esc_html__('Pickup', 'commonsbooking'); ?></div>
        <div><?php echo $booking->pickupDatetime(); ?></div>
    </div><!-- .cb-datetime -->
    <div class="cb-list-content cb-datetime cb-col-30-70">
        <div><?php echo esc_html__('Return', 'commonsbooking'); ?></div>
        <div><?php echo $booking->returnDatetime(); ?></div>
    </div><!-- .cb-bookigcode -->
    <?php
    if (
            $timeframe->showBookingCodes() &&
            $booking->getBookingCode() &&
            $booking->post_status == "confirmed")
    { // start if bookingcode
    ?>
        <div class="cb-list-content cb-datetime cb-col-30-70">
            <div><?php echo esc_html__('Booking Code', 'commonsbooking'); ?></div>
            <div><strong><?php echo $booking->getBookingCode(); ?></strong></div>
        </div>
    <?php
    } // end if bookingcode
    ?>
</div><!-- cb-booking-datetime -->

<!-- Location -->
<div class="cb-wrapper cb-booking-location">
    <div class="cb-list-header">
        <h3><?php echo esc_html__('Location: ', 'commonsbooking'); ?> <?php echo $location->title();?></h3>
    </div>
    <div class="cb-list-content cb-address cb-col-30-70">
        <div><?php echo esc_html__('Address', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedAddressOneLine(); ?></div>
    </div><!-- .cb-address -->
    <div class="cb-list-content cb-contact cb-col-30-70">
        <div><?php echo esc_html__('Contact', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedContactInfoOneLine(); ?></div>
    </div><!-- .cb-contact -->
    <div class="cb-list-content cb-pickupinstructions cb-col-30-70">
        <div><?php echo esc_html__('Pickup instructions', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedPickupInstructionsOneLine(); ?></div>
    </div><!-- .cb-cb-pickupinstructions -->
</div><!-- cb-booking-location -->

<!-- User TODO User Class so we can query everything the same way. -->
<div class="cb-wrapper cb-booking-user">
    <div class="cb-list-header">
        <h3><?php echo esc_html__('Your profile', 'commonsbooking'); ?></h3>
    </div>
    <div class="cb-list-content cb-user cb-col-30-70">
        <div><?php echo esc_html__('Your E-Mail', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'user_email'); ?></div>
    </div>
    <div class="cb-list-content cb-user cb-col-30-70">
        <div><?php echo esc_html__('Your User name', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'first_name'); ?> <?php echo CB::get('user', 'last_name'); ?><br>
        <?php echo CB::get('user', 'address'); ?>
        </div>
    </div>
</div>

<!-- Buttons & Form action -->
<div class="cb-action cb-wrapper">
    <?php $booking->bookingActionButton('confirm'); ?>
    <?php $booking->bookingActionButton('cancel'); ?>
</div>
