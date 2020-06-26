<h3>Items</h3>
<ul>
    <?php
        foreach($templateData['items'] as $item) {
            ?>
            <li>
                <a href="<?php echo get_permalink($item->ID); ?>"><?php echo $item->post_title; ?></a>
                <?php
                    foreach ($item->getBookableTimeFrames() as $bookableTimeFrame) {
                        $startDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'start-date', true));
                        $endDateTimestamp = intval(get_post_meta($bookableTimeFrame->ID, 'end-date', true));

                        $locationId = get_post_meta($bookableTimeFrame->ID, 'location-id', true);
                        $location = get_post($locationId);
                        $bookingUrl = get_permalink($locationId) . "&item=" . $item->ID;

                        echo "<br />" . $location->post_title . ": " . ($endDateTimestamp?"" :"Ab ") . date(get_option('date_format'), $startDateTimestamp);
                        if($endDateTimestamp) {
                            echo " - " . date(get_option('date_format'), $endDateTimestamp);
                        }
                        echo ' <a href="'.$bookingUrl.'">Buchen</a>';
                    }
                ?>
            </li>
            <?php
        }
    ?>
</ul>
