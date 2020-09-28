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

/** @var \CommonsBooking\Model\Location $location */
$location = $templateData['location'];
/** @var \CommonsBooking\Model\Item $item */
$item = $templateData['item'];

$button_label = __('Book item at this location', 'commonsbooking');
$permalink    = add_query_arg ( 'location', $location->ID, get_the_permalink($item->ID) );

$timeframes = $location->getBookableTimeframesByItem($item->ID, true);
?>

<?php echo $location->thumbnail(); // div.thumbnail is printed by function ?>
<div class="cb-list-info">
    <h4 class="cb-title cb-item-title"><?php echo $location->post_title; ?></h4>
    <?php
    /** @var \CommonsBooking\Model\Timeframe $timeframe */
    foreach($timeframes as $timeframe) {
        ?>
        <div class="cb-dates cb-timeframe-dates">
            <?php echo $timeframe->formattedBookableDate(); ?>
        </div>
        <?php
    }
    ?>

</div>
<div class="cb-action">
    <a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
</div>
