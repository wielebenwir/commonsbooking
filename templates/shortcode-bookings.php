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
$noResultText = esc_html__("No bookings available.", "commonsbooking");

if(count($templateData['bookings'])) {?>
    <table>
        <thead>
        <tr>
            <th>Startdatum</th>
            <th>Enddatum</th>
            <th>Item</th>
            <th>Location</th>
            <th>BookingDate</th>
            <th>User</th>
            <th>Status</th>
            <th>Aktionen</th>
        </tr>
        </thead>
        <tbody>
        <?php
        /** @var \CommonsBooking\Model\Booking $booking */
        foreach ($templateData['bookings'] as $booking) {
            $userInfo = get_userdata($booking->post_author);
            ?>
            <tr>
            <td><?php echo date('d.m.Y H:i', $booking->getStartDate()); ?></td>
            <td><?php echo date('d.m.Y H:i', $booking->getEndDate()); ?></td>
            <td><?php echo $booking->getItem()->title(); ?></td>
            <td><?php echo $booking->getLocation()->title(); ?></td>
            <td><?php echo date('d.m.Y H:i', strtotime($booking->post_date)); ?></td>
            <td><?php echo $userInfo->user_login; ?></td>
            <td><?php echo $booking->post_status; ?></td>
            <td><?php
                $editLink = get_permalink($booking->ID);
                if(commonsbooking_isCurrentUserAdmin()) {
                    $editLink = get_edit_post_link($booking->ID);
                }
                echo '<a href="' . $editLink . '">'.__('editieren', COMMONSBOOKING_PLUGIN_SLUG).'</a>';
            ?></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
<?php

} else {
    echo $noResultText;
}
