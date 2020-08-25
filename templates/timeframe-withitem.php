<?php 
/** 
* Timeframe summary with item
* 
* Post: Timeframe
* Use CB::get() to get data from the associacted location & item
*/

use CommonsBooking\CB\CB;

$item_permalink = CB::get('item', 'permalink');
$item_name	  	= CB::get('item', 'name');
$item_id	  	  = CB::get('item', 'id');
$button_text		= __('Book item', 'commonsbooking');
$dates			  	= CB::get('timeframe', 'dates');

?>
<?php if (has_post_thumbnail($item_id))
  echo get_the_post_thumbnail($item_id, 'thumbnail');
?>
<h4 class="cb-name cb-item-name"><?php echo $item_name; ?></h4>
<span class="cb-dates cb-timeframe-dates"><?php // echo $dates; ?></span>
<a href="<?php echo $item_permalink; ?>" class="cb-button"><?php echo $button_text; ?></a>
