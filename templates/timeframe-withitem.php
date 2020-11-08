<?php
$button_label = __('Book item', 'commonsbooking');
$permalink    = add_query_arg ( 'location', $location->ID, get_the_permalink($item->ID) ); // booking link set to item detail page with location ID
?>

<?php echo $item->thumbnail(); // div.thumbnail is printed by function ?>
<div class="cb-list-info">
    <h4 class="cb-title cb-item-title"><?php echo $item->post_title; ?></h4>
    <div class="cb-dates cb-timeframe-dates">
        <?php
        echo \CommonsBooking\Model\Timeframe::formatBookableDate($data['start_date'], $data['end_date']);
        ?>
    </div>
</div>
<div class="cb-action">
    <a href="<?php echo $permalink; ?>" class="cb-button"><?php echo $button_label; ?></a>
</div>
