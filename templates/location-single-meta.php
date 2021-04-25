<?php
/**
 * Location meta like address & pickupinfo
 *
 * WP Post properties for location are available as $location->property
 * location Model methods are available as $location->myMethod()
 *
 */

global $templateData;
$location = $templateData['location'];

$location_address   =  $location->formattedAddressOneLine();
$location_contact   =  $location->formattedContactInfoOneLine();
$pickup_instructions = $location->formattedPickupInstructions();
?>

<div class="cb-list-content cb-location-address cb-col-30-70">
  <div><?php echo esc_html__('Adress', 'commonsbooking'); ?></div>
  <div><?php echo $location->formattedAddressOneLine(); ?></div>
</div>

<?php if ( $location_contact ) { ?>
<div class="cb-list-content cb-location-contact cb-col-30-70">
  <div><?php echo esc_html__('Location contact', 'commonsbooking'); ?></div>
  <div><?php echo $location_contact; ?></div>
</div> <!-- .cb-cb-contact -->
<?php } // if ( $location_contact ) ?>

<?php if ($pickup_instructions) { ?>
<div class="cb-list-content cb-pickupinstructions cb-col-30-70">
        <div><?php echo esc_html__('Pickup instructions', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedPickupInstructionsOneLine(); ?></div>
    </div><!-- .cb-cb-pickupinstructions -->
<?php } // end iif ($pickup_instructions) ?>
