<?php
/**
 * Shortcode [cb_items]
 * Model: location
 *
 * List all items, with one or more associated timeframes (with location info)
 *
 * WP Post properties for locations are available as $item->property
 * location Model methods are available as $item->myMethod()
 *
 */


global $templateData;
$item = new \CommonsBooking\Model\Item($templateData['item']);
$hasTimeFrames = (array_key_exists('data', $templateData) && count($templateData['data']));

// the item-not-available message (if item ist currently not available) can be defined via plugin options -> message templates
$noResultText = \CommonsBooking\Settings\Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_templates', 'item-not-available' );


?>
<div class="cb-list-header">
    <?php echo $item->thumbnail(); ?>
    <div class="cb-list-info">
        <h2><?php echo $item->titleLink(); ?></h2>
        <?php echo $item->excerpt(); ?>
        <?php if (! $hasTimeFrames) { ?>
            <div class="cb-status cb-availability-status cb-status-not-available cb-notice-small"><?php echo ( $noResultText ); ?></div>
       <?php } ?>
    </div>
</div><!-- .cb-list-header -->

<?php
if ($hasTimeFrames) {
    foreach ($templateData['data'] as $locationId => $data ) {
        $location = new \CommonsBooking\Model\Location($locationId);
        set_query_var( 'item', $item );
        set_query_var( 'location', $location );
        set_query_var( 'data', $data );
        commonsbooking_get_template_part( 'timeframe', 'withlocation' );
    }
 }; // end if ($timeframes) ?>