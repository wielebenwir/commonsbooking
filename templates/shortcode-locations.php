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

$titleLink 		= sprintf('<h2><a href="%s">%s</a></h2>', get_the_permalink($location->ID), $location->post_title );
$thumbnail 		= ( has_post_thumbnail($location->ID) ) ? get_the_post_thumbnail($location->ID) : '';
$timeframes 	= $location->getBookableTimeframes();
$noResultText = __("No bike available at this location.", "commonsbooking");
?>

<div class="cb-list-header">
	<?php echo $titleLink; ?>
	<?php echo $thumbnail; ?>
</div><!-- .cb-list-header -->

<div class="cb-list-content">
	<?php echo get_the_excerpt($location->ID); ?>
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
