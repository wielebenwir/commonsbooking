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

    <table
            id="table"
            data-toggle="table"
            data-ajax="ajaxRequest"
            data-pagination="true"
            data-search="true"
            data-side-pagination="server"
            data-filter-control="true"
            data-cookie="true"
            data-cookie-id-table="saveId"
    >
        <thead>
        <tr>
            <th data-field="startDate" data-sortable="true">Startdatum</th>
            <th data-field="endDate" data-sortable="true">Enddatum</th>
            <th data-field="item" data-sortable="true" data-filter-control="input">Item</th>
            <th data-field="location" data-sortable="true" data-filter-control="input">Location</th>
            <th data-field="bookingDate" data-sortable="true">BookingDate</th>
            <th data-field="user" data-sortable="true" data-filter-control="input">User</th>
            <th data-field="status" data-sortable="true" data-filter-control="input">Status</th>
            <th data-field="actions">Aktionen</th>
        </tr>
        </thead>
    </table>

<?php

} else {
    echo $noResultText;
}
