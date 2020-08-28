<?php 
/** 
* Timeframe summary with item
* 
* WP Post properties for timeframe are available as $timeframe->property
* Timeframe Model methods are available as $timeframe->myMethod()   
* 
* Model: Timeframe
*/

$item           = $timeframe->getItem();
$location       = $timeframe->getLocation();
$thumbnail 		= ( has_post_thumbnail($item->ID) ) ? get_the_post_thumbnail($item->ID) : '';
$button_label   = __('Book item', 'commonsbooking');
$permalink      = add_query_arg ( 'item', $item->ID, get_the_permalink($location->ID) );
?>

<h4 class="cb-name cb-item-name"><?php echo $item->post_title; ?></h4>
<?php echo $thumbnail; ?>
<?php echo $location->post_title; ?>
<span class="cb-dates cb-timeframe-dates"><?php echo $timeframe->residence(); ?></span>
<a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
