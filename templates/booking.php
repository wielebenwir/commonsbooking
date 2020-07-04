<?php

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;

// \CommonsBooking\View\Booking::unconfirmed();

    // Beispiel um Infos bzgl. Bucbarem Timeframe zu finden.
     $booking = new \CommonsBooking\Model\Booking($post->ID);
     $timeframe = $booking->getBookableTimeFrame();
?>

<div class="cb-notice">
    <?php echo CB::get('booking', 'booking_notice'); ?>
</div><!-- .cb-table -->

<div id="cb-action">
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'confirm') ?>
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'cancel') ?>
</div>
<div class="cb-table">
    <!-- <div class="cb-timeframe">
        <div class="cb-datetime-start">
            <span class="cb-date"><?php echo CB::get('booking', 'booking_timeframe_date'); ?></span>
         <div class="cb-divider">&rarr;</div>
        <div class="cb-datetime-end">
            <span class="cb-date">24.3.2020</span> <span class="cb-time">9:00</span>
        </div>
        cb-timeframe-end 
    </div> cb-timeframe -->
    <div class="cb-location">
        <div class="cb-table-header">
            <h3><span class="cb-location-name"><?php echo __('Booking information', 'commonsbooking'); ?></span></h3>
            <!-- <img src="https://via.placeholder.com/50"> -->
        </div><!-- . cb-table-header-->
        <div class="cb-meta cb-location-meta">

        <div class="cb-address col-30-70">
                <div><?php echo __('Item', 'commonsbooking'); ?></div>
                <div><?php echo CB::get('item', 'name'); ?></div>
            </div><!-- .cb-address -->

        <div class="cb-address col-30-70">
                <div><?php echo __('Pickup', 'commonsbooking'); ?></div>
                <div><?php echo CB::get('booking', 'pickup_datetime'); ?></div>
            </div><!-- .cb-address -->

        <div class="cb-address col-30-70">
            <div><?php echo __('Return', 'commonsbooking'); ?></div>
            <div><?php echo CB::get('booking', 'return_datetime'); ?></div>
        </div><!-- .cb-address -->

        <?php if (!empty(CB::get( 'location', CB_METABOX_PREFIX . 'location_pickupinstructions'))) { ?>
        <div class="cb-pickup-info col-30-70">
            <div><?php echo __('Pickup and return information', 'commonsbooking'); ?></div>
            <div><?php echo CB::get( 'location', CB_METABOX_PREFIX . 'location_pickupinstructions') ?></div>
        </div><!-- .cb-pickup-info -->
         <?php } // end if pickupinstructions ?>


            <div class="cb-address col-30-70">
                <div><?php echo __('Address', 'commonsbooking'); ?></div>
                <div><?php echo CB::get('location', 'location_address'); ?></div>
        </div><!-- .cb-address -->

        <?php if (!empty(CB::get( 'location', CB_METABOX_PREFIX . 'location_contact'))) { ?>
        <div class="cb-pickup-info col-30-70">
            <div><?php echo __('Location contact', 'commonsbooking'); ?></div>
            <div><?php echo nl2br(CB::get( 'location', CB_METABOX_PREFIX . 'location_contact')) ?></div>
        </div><!-- .cb-pickup-info -->
         <?php } // end if location contact ?>

        </div><!-- .cb-location-meta -->
    </div><!-- .cb-location -->


    <div class="cb-user">
        <div class="cb-table-header">
            <h3><span class="cb-user-name"><?php echo __('Your profile data', 'commonsbooking'); ?></span></h3>
        </div><!-- . cb-table-header-->
        <div class="cb-meta cb-user-meta">
            <div class="cb-user-info col-30-70">       
                <div><?php echo __('User E-Mail', 'commonsbooking') ?></div>
                <div><?php CB::get('user', 'user_email'); ?></div>
                <div><?php echo __('Login Name', 'commonsbooking') ?></div>
                <div><?php CB::get('user', 'user_login'); ?></div>
                <div><?php echo __('User Name', 'commonsbooking') ?></div>
                <div><?php echo CB::get('user', 'first_name'); ?> <?php echo CB::get('user', 'last_name'); ?><br>
                <?php echo CB::get('user', 'user_address'); ?> 
        </div>
            </div><!-- .cb-user-info -->
        </div><!-- .cb-user-meta -->
    </div><!-- .cb-user -->
</div><!-- .cb-table -->

<div id="cb-action">
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'confirm') ?>
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'cancel') ?>
</div>
