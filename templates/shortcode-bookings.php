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

if(count($templateData['bookings'])) {

//    var_dump($templateData['bookings']);
    ?>


<!--    <label for="filters-search-input" class="filter-label">Search</label>-->
<!--    <input style="font-size: 1rem" class="textfield filter__search js-shuffle-search" type="search" id="filters-search-input" />-->


    <p class="filter-label">Filter</p>
    <div class="filter-options">
        <button style="font-size: 1rem" data-group="space">Space</button>
        <button style="font-size: 1rem" data-group="nature">Nature</button>
        <button style="font-size: 1rem" data-group="animal">Animal</button>
        <button style="font-size: 1rem" data-group="city">City</button>
    </div>

<!--    <legend class="filter-label">Sort</legend>-->
<!--    <input type="radio" name="sort-value" value="dom" checked /> Default-->
<!--    <input type="radio" name="sort-value" value="title" /> Title-->
<!--    <input type="radio" name="sort-value" value="date-created" /> Date Created-->

    <div class="container">
        <div id="grid" class="row my-shuffle-container">
        </div>
    </div>


<?php

} else {
    echo $noResultText;
}
