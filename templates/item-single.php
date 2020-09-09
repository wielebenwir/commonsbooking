<?php
    /**
     * Single item calendar with booking functionality
     *
     * Used on item single
     */
    global $templateData;
    $templateData = \CommonsBooking\View\Item::getTemplateData();
    $noResultText = __("This item is currently not available.", "commonsbooking");

    cb_get_template_part( 'item', 'single-meta' ); // file: location-single-meta.php

    // Single Item View
    if(array_key_exists('location', $templateData) && $templateData['location']) { // item selected, so we display the booking calendar
        echo '<h2>' . __( 'Book this location', 'commonsbooking') . '</h2>';
        cb_get_template_part( 'location', 'calendar-header' ); // file: item-calendar-header.php
        cb_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php
    }

    // Multi item view
    if(array_key_exists('locations', $templateData) && $templateData['locations']) {
        foreach ($templateData['locations'] as $location ) {
            $templateData['location'] = $location;
            cb_get_template_part( 'item', 'withlocation' ); // file: location-withitem.php
        }  // end foreach $timeframes
    } // $item_is_selected

    if(!array_key_exists('location', $templateData) && !array_key_exists('locations', $templateData)) { ?>
        <div class="cb-status cb-availability-status cb-no-residency"><?php echo ( $noResultText );
    }
?>
