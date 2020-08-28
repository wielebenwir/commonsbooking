<?php
/** 
 * Shortcode [cb_items]
 * Model: Item
 *
 * List all items, with one or more associated timeframes (with location info)
 * 
 * WP Post properties for items are available as $item->property
 * Item Model methods are available as $item->myMethod()    
 *  
 */

$titleLink 		= sprintf('<h2><a href="%s">%s</a></h2>', get_the_permalink($item->ID), $item->post_title );
$thumbnail 		= ( has_post_thumbnail($item->ID) ) ? get_the_post_thumbnail($item->ID) : '';
$timeframes 	= $item->getBookableTimeframes(); // @TODO: Model 
$noResultText = __("This item is currently not available.", "commonsbooking");

?>
<div class="cb-list-header">
	<?php echo $titleLink; ?>
	<?php echo $thumbnail; ?>
</div><!-- .cb-list-header -->

<div class="cb-list-content">
	<?php echo get_the_excerpt($item->ID); ?>
</div><!-- .cb-list-content -->

<?php 
	if ($timeframes) {
		foreach ($timeframes as $timeframe ) { 
			set_query_var( 'timeframe', $timeframe );
			cb_get_template_part( 'timeframe', 'withlocation' ); // file: timeframe-withlocation.php
		} 
	} else { ?>
		<div class="cb-status cb-availability-status cb-no-residency"><?php echo ( $noResultText ); ?>
<?php } // end if ($timeframes) ?>
