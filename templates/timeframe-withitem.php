<?php 
/** 
* Timeframe summary with item
* 
* WP Post properties for timeframe are available as $timeframe->property
* Timeframe Model methods are available as $timeframe->myMethod()   
* 
* Model: Timeframe
*/

$item           = $timeframe->getitem();
$thumbnail 		= ( has_post_thumbnail($item->ID) ) ? get_the_post_thumbnail($item->ID) : '';
$button_label   = __('Book item', 'commonsbooking');

?>

<h4 class="cb-name cb-item-name"><?php echo $item->post_title; ?></h4>
<?php echo $thumbnail; ?>
<span class="cb-dates cb-timeframe-dates"><?php echo $timeframe->residence(); ?></span>
<a href="<?php echo get_the_permalink($item->ID); ?>" class="cb-button"><?php echo $button_label; ?></a>
