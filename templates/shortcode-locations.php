<?php
/** 
 * Shortcode [cb_locations]
 *
 * List all locations with one or more associated timeframes + items
 *  
 * Post: Item
 */

use CommonsBooking\Repository\Timeframe;

$timeframes 	= Timeframe::getBookable($post->ID);
$noResultText = __("This item is currently not available.", "commonsbooking");

// We should not need to query here, the item $post should contain an array of $timeframes ( id1, id2, ... ) 
// so we could just do: foreach ( $post->timeframes as $timeframe )

?>
<div class="cb-list-header">
	<?php the_title( '<h2><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
	<?php if (has_post_thumbnail())
		the_post_thumbnail('thumb');
	?>
</div><!-- .cb-list-header -->

<div class="cb-list-content">
	<?php the_excerpt(); ?>
</div><!-- .cb-list-content -->

<?php if ($timeframes) {
		foreach ($timeframes as $timeframeID ) { 
			$post   = get_post( $timeframeID );
			setup_postdata( $post );
			cb_get_template_part('timeframe', 'withitem'); // file timeframe-withitem.php
		} 
	} else {
		echo ($noResultText);
	} // end if ($timeframes)
	?>
