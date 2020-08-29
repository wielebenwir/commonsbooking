<?php
/**
 * Location meta like address & pickupinfo
 * 
 * WP Post properties for location are available as $location->property
 * location Model methods are available as $location->myMethod()   
 * 
 */

$location_address   =  $location->formattedAddressOneLine();
$location_contact   =  $location->formattedContactInfoOneLine();

?>

<div class="cb-list-content cb-location-address cb-2col">
  <div><?php echo __('Adress', 'commonsbooking'); ?></div>
  <div><?php echo $location->formattedAddressOneLine(); ?></div>
</div>
<?php if ( $location_contact ) { ?>
<div class="cb-list-content cb-location-pickup-instructions cb-2col">
  <div><?php echo __('Location contact', 'commonsbooking'); ?></div>
  <div><?php echo $location_contact; ?></div>
</div>
<?php } // if ( $location_contact ) 