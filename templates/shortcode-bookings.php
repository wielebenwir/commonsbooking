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

if ($templateData['total'] > 0) {

    ?>
    <div class="booking-list">
        <div class="booking-list--filters">
    <?php

    echo "<div class=\"filter-wrapper\">";
    echo "<p class=\"filter-label\">Startdatum</p>";
    echo "<div class=\"filter-startdate\">";
    echo '<input id="startDate-datepicker" type="text" value="">';
    echo '<input id="startDate" type="hidden" value="">';
    echo '</div>';
    echo '</div>';

    echo "<div class='filter-wrapper'>";
    echo "<p class=\"filter-label\">Enddatum</p>";
    echo "<div class=\"filter-enddate\">";
    echo '<input id="endDate-datepicker" type="text" value="">';
    echo '<input id="endDate" type="hidden" value="">';
    echo '</div>';
    echo '</div>';


    foreach ($templateData['filters'] as $label => $values) {
        echo "<div class='filter-wrapper'>";
        echo "<p class=\"filter-label\">" . __(ucfirst($label), 'commonsbooking') . "</p>";
        echo "<div class=\"filter-".$label."s\">";
        echo sprintf('<select class="select2">');

        echo sprintf('<option type="checkbox" value="all" selected="selected">%s</option>', __('All', 'commonsbooking'));

        foreach ($values as $value) {
            echo sprintf('<option type="checkbox" value="%s">%s</option>', $value, $value);
        }
        echo '</select>
        </div></div>';
    }
?>
        </div>
        <div id="booking-list--results">
            <div class="my-sizer-element"></div>
        </div>
        <div id="booking-list--pagination" style="display: none"></div>
<?php
} else {
    echo $noResultText;
}
