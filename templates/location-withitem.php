<?php
/**
 * Timeframe summary with item
 *
 * WP Post properties for timeframe are available as $timeframe->property
 * Timeframe Model methods are available as $timeframe->myMethod()
 *
 * Model: Timeframe
 */

use CommonsBooking\Settings\Settings;
global $templateData;


/** @var \CommonsBooking\Model\Location $location */
$location = $templateData['location'];
/** @var \CommonsBooking\Model\Item $item */
$item         = $templateData['item'];
$button_label = Settings::getOption( 'commonsbooking_options_templates', 'label-booking-button' );
$permalink    = add_query_arg( 'cb-location', $location->ID, get_the_permalink( $item->ID ) ); // booking link set to item detail page with location ID


$timeframes = $item->getBookableTimeframesByLocation( $location->ID, true );
?>

<?php echo commonsbooking_sanitizeHTML( $item->thumbnail( 'cb_listing_medium' ) ); // div.thumbnail is printed by function ?>

<div class="cb-list-info">
	<h4 class="cb-title cb-item-title"><?php echo commonsbooking_sanitizeHTML( $item->post_title ); ?></h4>
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
