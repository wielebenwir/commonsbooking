<?php
/** 
 * Shortcode [cb_locations]
 * Model: location
 *
 * List all locations, with one or more associated timeframes (with location info)
 * 
 * WP Post properties for locations are available as $location->property
 * location Model methods are available as $location->myMethod()    
 *  
 */

$timeframes 	= $location->getBookableTimeframes();
$noResultText = __("No bike available at this location.", "commonsbooking");

?>

<div class="cb-list-header">
	<?php echo $location->thumbnail(); ?>
	<h2><?php echo $location->titleLink(); ?></h2>
</div><!-- .cb-list-header -->

<div class="cb-list-content">
	<?php echo $location->excerpt(); ?>
</div><!-- .cb-list-content -->

<?php 
	if ($timeframes) {
		foreach ($timeframes as $timeframe ) { 
			set_query_var( 'timeframe', $timeframe );
			cb_get_template_part( 'timeframe', 'withitem' ); // file: timeframe-withlocation.php
		} 
	} else { ?>
		<div class="cb-status cb-availability-status"><?php echo ( $noResultText ); ?>
	<?php } // end if ($timeframes) ?>
