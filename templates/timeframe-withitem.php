<?php

use CommonsBooking\Settings\Settings;
$button_label = Settings::getOption('commonsbooking_options_templates', 'label-booking-button');
$permalink    = add_query_arg ( 'cb-location', $location->ID, get_the_permalink($item->ID) ); // booking link set to item detail page with location ID
?>

<?php echo commonsbooking_sanitizeHTML($item->thumbnail('cb_listing_small')); // div.thumbnail is printed by function ?>
<div class="cb-list-info">
    <h4 class="cb-title cb-item-title">
        <a href=" <?php echo commonsbooking_sanitizeHTML( get_permalink( $item->ID ) ); ?> ">
            <?php echo commonsbooking_sanitizeHTML($item->post_title); ?>
        </a>
    </h4>
    <div class="cb-dates cb-timeframe-dates">
        <?php
            if(
                array_key_exists('ranges', $data) &&
                count($data['ranges'])
            ) {
	            $ranges = \CommonsBooking\Helper\Helper::mergeRangesToBookableDates($data['ranges']);
                foreach ($ranges as $range) {
                    echo commonsbooking_sanitizeHTML( \CommonsBooking\Model\Timeframe::formatBookableDate($range['start_date'], $range['end_date']) ) . '<br>';
                }
            }
        ?>
    </div>
</div>
<div class="cb-action">
    <a href="<?php echo esc_url($permalink); ?>" class="cb-button"><?php echo commonsbooking_sanitizeHTML($button_label); ?></a>
</div>
