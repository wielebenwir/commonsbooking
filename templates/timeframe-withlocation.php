<?php 
/** 
* Timeframe summary with location
* 
* Post: Timeframe
* Use CB::get() to get data from the associacted location & item
*/

use CommonsBooking\CB\CB;

$location_permalink = CB::get('location', 'permalink');
$location_name	  	= CB::get('location', 'name');
$location_address   = CB::get('location', 'address');
$button_text		    = __('Book here', 'commonsbooking');
$dates			  	    = CB::get('timeframe', 'dates');

?>

<h4 class="cb-name cb-location-name"><?php echo $location_name; ?></h4>
<span class="cb-dates cb-timeframe-dates"><?php echo $dates; ?></span>
<span class="cb-address cb-location-address"><?php echo $location_address; ?></span>
<a href="<?php echo $location_permalink; ?>" class="cb-button"><?php echo $button_text; ?></a>
