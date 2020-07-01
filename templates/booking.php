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
<div class="cb-table">
    <div class="cb-timeframe">
        <div class="cb-datetime-start">
            <span class="cb-date"><?php echo CB::get('booking', 'booking_timeframe_date'); ?></span>
        <!-- <div class="cb-divider">&rarr;</div>
        <div class="cb-datetime-end">
            <span class="cb-date">24.3.2020</span> <span class="cb-time">9:00</span> -->
        </div><!-- .cb-timeframe-end -->
    </div><!-- .cb-timeframe -->
    <div class="cb-location">
        <div class="cb-table-header">
            <h3>Pickup and return at: <span class="cb-location-name"><?php echo CB::get('location', 'name'); ?></span></h3>
            <img src="https://via.placeholder.com/50">
        </div><!-- . cb-table-header-->
        <div class="cb-meta cb-location-meta">
        <div class="cb-address col-30-70">
                <div><?php echo __('Pickup and return', 'commonsbooking'); ?></div>
                <div><?php echo CB::get('booking', 'pickup_datetime'); ?></div>
            </div><!-- .cb-address -->
            <div class="cb-address col-30-70">
                <div>Adress</div>
                <div><?php echo CB::get('location', 'location_address'); ?></div>
            </div><!-- .cb-address -->
            <div class="cb-pickup-info col-30-70">
                <div>Pickup</div>
                <div><?php echo CB::get( 'location', CB_METABOX_PREFIX . 'location_pickupinstructions') ?></div>
            </div><!-- .cb-pickup-info -->
        </div><!-- .cb-location-meta -->
    </div><!-- .cb-location -->
    <div class="cb-item">
        <div class="cb-table-header">
            <h3>Item: <span class="cb-item-name"><?php echo CB::get('item', 'name'); ?></span></h3>
            <img src="https://via.placeholder.com/50">
        </div><!-- . cb-table-header-->
        <div class="cb-meta cb-item-meta">
           Quia aut modi et voluptates aperiam ducimus. Ipsam et illo qui quaerat soluta consequuntur. Debitis consequuntur sit ipsum nihil. Cum qui sed aliquid voluptas adipisci. Reiciendis id at quis magnam quia eum. Quis error et sint minus eaque voluptas voluptatem.           
        </div><!-- .cb-item-meta -->
    </div><!-- .cb-item -->
    <div class="cb-user">
        <div class="cb-table-header">
            <h3><span class="cb-user-name">My profile</span></h3>
        </div><!-- . cb-table-header-->
        <div class="cb-meta cb-user-meta">
            <div class="cb-user-info col-30-70">       
                <div><?php CB::get('user', 'user_email'); ?></div>
                <div>sowieso@dsad.com</div>
                <div>Username:</div>
                <div>dev</div>
                <div>Name</div>
                <div>Martin Mustermann</div>
            </div><!-- .cb-user-info -->
        </div><!-- .cb-user-meta -->
    </div><!-- .cb-user -->
</div><!-- .cb-table -->

<div id="cb-action">
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'confirm') ?>
    <?php echo CB::get('booking', 'booking_action_button', NULL, 'cancel') ?>
</div>
