<?php
/**
 * Location meta data table
 * 
 */

use CommonsBooking\CB\CB;

$address      = CB::get('location', 'address');
$pickup_info  = CB::get('location', '_cb_location_pickupinstructions');

?>

 <div class="cb-table">
    <div class="cb-meta cb-location-meta">
      <div class="cb-address cb-cols col-30-70">
          <div><?php echo __('Adress', 'commonsbooking'); ?></div>
          <div><?php echo $address; ?></div>
      </div><!-- .cb-address -->
      <?php if ( $pickup_info  ) { ?>
      <div class="cb-address cb-cols col-30-70">
          <div><?php echo __('Pickup instructions', 'commonsbooking'); ?></div>
          <div><?php echo $pickup_info; ?></div>
      </div><!-- .cb-address -->
      <?php } // end if pickup_info ?>
  </div><!-- .cb-location-meta -->
