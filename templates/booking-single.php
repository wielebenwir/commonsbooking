<?php

/**
 * Booking Single
 */

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;

//@TODO: I removed the second confirmation button from the top.   

$booking       = new \CommonsBooking\Model\Booking($post->ID);
$timeframe     = $booking->getBookableTimeFrame();
$location      = $booking->getLocation();
$item          = $booking->getItem();
?>

<div class="cb-notice">
    <?php echo CB::get('booking', 'booking_notice'); ?>
</div><!-- .cb-notice -->

<div class="cb-wrapper cb-booking-item">
    <div class="cb-list-header cb-2col">
        <?php echo $item->thumbnail();?>
        <h3><?php echo __('Item', 'commonsbooking'); ?>: <?php echo $item->titleLink(); ?></h3>
        <?php echo $item->excerpt();?>
    </div>
</div>

<div class="cb-wrapper cb-booking-datetime">
    <div class="cb-list-header cb-2col cb-datetime">
        <div><?php echo __('Pickup from', 'commonsbooking'); ?></div>
        <div><?php echo $booking->pickup_datetime(); ?></div>
    </div><!-- .cb-datetime -->
        <div class="cb-list-content cb-datetime cb-2col">
            <div><?php echo __('Return until', 'commonsbooking'); ?></div>
            <div><?php echo $booking->return_datetime(); ?></div>
        </div><!-- .cb-address -->
    </div><!-- .cb-list-header -->
</div><!-- cb-booking-datetime -->

<!-- Location -->
<div class="cb-wrapper cb-booking-location">
    <div class="cb-list-header">
        <h3><?php echo $location->titleLink();?></h3>
    </div>
    <div class="cb-list-content cb-address cb-2col">
        <div><?php echo __('Address', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedAddressOneLine(); ?></div>
    </div><!-- .cb-address -->
    <div class="cb-list-content cb-contact cb-2col">
        <div><?php echo __('Contact', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedContactInfoOneLine(); ?></div>
    </div><!-- .cb-contact -->
    <div class="cb-list-content cb-pickupinstructions cb-2col">
        <div><?php echo __('Pickup instructions', 'commonsbooking'); ?></div>
        <div><?php echo $location->pickupInstructions(); ?></div>
    </div><!-- .cb-cb-pickupinstructions -->
</div><!-- cb-booking-location -->

<!-- User @TODO: User Class so we can query everything the same way. -->
<div class="cb-wrapper cb-booking-user">
    <div class="cb-list-header">
        <h3><?php echo __('Your profile', 'commonsbooking'); ?></h3>
    </div>
    <div class="cb-list-content cb-user cb-2col">
        <div><?php echo __('Your E-Mail', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'user_email'); ?></div>
        <div><?php echo __('Your User name', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'first_name'); ?> <?php echo CB::get('user', 'last_name'); ?><br>
        <?php echo CB::get('user', 'user_address'); ?> 
        </div>
    </div>
</div>

<!-- Buttons & Form action -->
<div class="cb-action cb-wrapper">
    <?php $booking->booking_action_button('confirm'); ?>
    <?php $booking->booking_action_button('cancel'); ?>
</div>
