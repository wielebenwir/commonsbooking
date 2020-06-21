<?php
    /**
     * Single item calendar with booking functionality
     *
     * Used on item single
     */
    \CommonsBooking\View\Location::index();

    # Multi Item View
    /*
    if(array_key_exists('items', $templateData) && $templateData['items']) {
        #{% include '/location/components/items.html.twig'  with {'items': items} %}
        include __DIR__ . '/components/location-items.php';
    }

    if(!array_key_exists('item', $templateData) && !array_key_exists('items', $templateData)) {
        echo 'Keine buchbaren Items an Location';
    }
    */
?>
