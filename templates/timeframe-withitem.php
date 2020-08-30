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
$button_label   = __('Book item', 'commonsbooking');
$permalink      = add_query_arg ( 'item', $item->ID, get_the_permalink($location->ID) );
?>

<h4 class="cb-name cb-item-name"><?php echo $item->post_title; ?></h4>
<?php echo $item->thumbnail(); ?>
<span class="cb-dates cb-timeframe-dates"><?php echo $timeframe->formattedBookableDate(); ?></span>
<a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
