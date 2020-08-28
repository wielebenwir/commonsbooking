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
<h4 class="cb-name cb-location-name"><?php echo $location->post_title; ?></h4>
<span class="cb-dates cb-timeframe-dates"><?php echo $timeframe->formattedBookableDate(); ?></span>
<span class="cb-address cb-location-address"><?php echo $location->address(); ?></span>
<a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
