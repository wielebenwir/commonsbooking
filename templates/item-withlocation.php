<?php
/**
 * Timeframe summary with item
 *
 * WP Post properties for timeframe are available as $timeframe->property
 * Timeframe Model methods are available as $timeframe->myMethod()
 *
 * Model: Timeframe
 */
global $templateData;
$button_label = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'label-booking-button' );

/** @var \CommonsBooking\Model\Location $location */
$location = $templateData['location'];
/** @var \CommonsBooking\Model\Item $item */
$item = $templateData['item'];

$permalink = add_query_arg( 'cb-location', $location->ID, get_the_permalink( $item->ID ) ); // booking link set to item detail page with location ID

$timeframes = $location->getBookableTimeframesByItem( $item->ID, true );
?>

<?php echo commonsbooking_sanitizeHTML( $location->thumbnail( 'cb_listing_small' ) ); // div.thumbnail is printed by function ?>
<div class="cb-list-info">
	<h4 class="cb-title cb-location-title"><?php echo commonsbooking_sanitizeHTML( $location->post_title ); ?></h4>
	<?php
	/** @var \CommonsBooking\Model\Timeframe $timeframe */
	foreach ( $timeframes as $timeframe ) {
		?>
		<div class="cb-dates cb-timeframe-dates">
			<?php echo commonsbooking_sanitizeHTML( $timeframe->formattedBookableDate() ); ?>
		</div>
		<?php
	}
	?>

</div>
<div class="cb-action">
	<a href="<?php echo esc_url( $permalink ); ?>" class="cb-button"><?php echo commonsbooking_sanitizeHTML( $button_label ); ?></a>
</div>
