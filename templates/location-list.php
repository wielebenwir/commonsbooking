<?php
    foreach ($templateData['locations'] as $location) {
        ?>
        <div class="cb-item-wrapper cb-box" >
            <h2 class="cb-big"><a href="<?php echo get_permalink($location->ID); ?>"><?php echo $location->post_title; ?></a></h2>
            <div class="cb-list-item-description">
                <div class="align-left">
                    <?php echo get_the_post_thumbnail($location->ID, 'thumbnail'); ?>
                </div>
                <?php echo $location->post_content; ?>
            </div>
            <div class="cb-table">
                <?php
                foreach ($location->getBookableTimeFrames() as $bookableTimeFrame) {
                    $startDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'start-date', true));
                    $endDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'end-date', true));

                    $itemId = get_post_meta($bookableTimeFrame->ID, 'item-id', true);
                    $item = get_post($itemId);
                    $bookingUrl = get_permalink($location->ID) . "&item=" . $itemId;
                    $dateString = ($endDateTimestamp ? "" : "Ab ") . date_i18n(get_option('date_format'),
                            $startDateTimestamp);
                    if ($endDateTimestamp) {
                        $dateString .= " - " . date_i18n(get_option('date_format'), $endDateTimestamp);
                    }
                    ?>
                    <div class="cb-row">
                        <a href="<?php echo $bookingUrl; ?>" class="cb-button align-right"> Hier buchen</a>
                        <span class="cb-date"><?php echo $dateString; ?></span>
                        <span class="cb-location-name"><?php echo $item->post_title; ?></span>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
<?php
    }
?>
