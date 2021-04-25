<?php
    global $templateData;
    $location =  $templateData['location'];
    echo $location->thumbnail(array ('200')); // div.thumbnail is printed by function
?>
  <div class="cb-list-info">
      <h4 class="cb-title cb-location-title"><?php echo $location->post_title; ?></h4>
      <div class="cb-address cb-location-address"><?php echo $location->formattedAddressOneLine(); ?></div>
      <div class="cb-address cb-location-pickupinstructions"><?php echo $location->formattedPickupInstructions(); ?></div>
    </div>

