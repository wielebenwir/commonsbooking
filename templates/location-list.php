<?php

/**
 * deprecated
 * this template is not in use anymore and will be deleted
 * todo delete unused template after final check
 */

    global $templateData;

    foreach ($templateData['locations'] as $location) {
        ?>
        <div class="cb-item-wrapper cb-box" >
            <h2 class="cb-big"><a href="<?php echo get_permalink($location->ID); ?>"><?php echo commonsbooking_sanitizeHTML($location->post_title); ?></a></h2>
            <div class="cb-list-item-description">
                <div class="align-left">
                    <?php echo get_the_post_thumbnail($location->ID, 'thumbnail'); ?>
                </div>
                <?php echo commonsbooking_sanitizeHTML($location->post_content); ?>
            </div>
            <div class="cb-table">
                <?php
                $startDates = [];
                foreach ($location->getBookableTimeFrames() as $bookableTimeFrame) {
                    $startDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'repetition-start', true));
                    $formattedStartDate = date(get_option('date_format'), $startDateTimestamp);
                    $endDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'repetition-end', true));
                    $formattedEndDate = date(get_option('date_format'), $endDateTimestamp);

                    $itemId = get_post_meta($bookableTimeFrame->ID, 'item-id', true);
                    $item = get_post($itemId);

                    // Continue if we have the same startdate for this item
                    if(array_key_exists($itemId, $startDates) && in_array($formattedStartDate, $startDates)) continue;
                    $startDates[$itemId] = $formattedStartDate;


                    $bookingUrl = add_query_arg('item', $itemId, get_permalink($location->ID) );
                    $dateString = ($endDateTimestamp ? "" : "Ab ") . $formattedStartDate;
                    if ($endDateTimestamp) {
                        $dateString .= " - " . $formattedEndDate;
                    }
                    ?>
                    <div class="cb-row">
                        <a href="<?php echo esc_url($bookingUrl); ?>" class="cb-button align-right"> Hier buchen</a>
                        <span class="cb-date"><?php echo commonsbooking_sanitizeHTML($dateString); ?></span>
                        <span class="cb-location-name"><?php echo commonsbooking_sanitizeHTML($item->post_title); ?></span>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
<?php
    }
?>
