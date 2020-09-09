<?php 
/** 
* Timeframe summary with location
* 
* WP Post properties for timeframe are available as $timeframe->property
* Timeframe Model methods are available as $timeframe->myMethod()   
* 
* Model: Timeframe
*/

$location       = $timeframe->getLocation();
$item           = $timeframe->getItem();
$button_label   = __('Book here', 'commonsbooking');
$permalink      = add_query_arg ( 'item', $item->ID, get_the_permalink($location->ID) );

?>
<?php echo $location->thumbnail(); // div.thumbnail is printed by function ?>
<div class="cb-list-info">
  <h4 class="cb-title cb-location-title"><?php echo $location->post_title; ?></h4>
  <div class="cb-address cb-location-address"><?php echo $location->formattedAddressOneLine(); ?></div>
  <div class="cb-dates cb-timeframe-dates"><?php echo $timeframe->formattedBookableDate(); ?></div>
</div>
<div class="cb-action">
  <a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
</div>
