<?php
	global $templateData;
	$item = $templateData['item'];

	do_action( 'commonsbooking_before_item-calendar-header', $item->ID, $item );

	echo commonsbooking_sanitizeHTML( $item->thumbnail( 'cb_listing_medium' ) ); // div.thumbnail is printed by function
?>
<div class="cb-list-info">
	<h4 class="cb-title cb-item-title"><?php echo commonsbooking_sanitizeHTML( $item->post_title ); ?></h4>
</div>

<?php
	do_action( 'commonsbooking_after_item-calendar-header', $item->ID, $item );
?>