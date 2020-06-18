<?php
    /**
     * Single item calendar with booking functionality
     *
     * Used on item single
     */
    #\CommonsBooking\View\Location::index();
    $templateData = \CommonsBooking\View\Location::getTemplateData();
?>

<h2>Buchungskalender</h2>
<?php
    $post = $templateData['post'];

    # Single Item View
    if(array_key_exists('item', $templateData) && $templateData['item']) {
        include __DIR__ . '/components/location-item.php';
        #{% include '/location/components/item.html.twig'  with {'item': item, 'location': location, 'type': type} %}
    }

    # Multi Item View
    if(array_key_exists('items', $templateData) && $templateData['items']) {
        #{% include '/location/components/items.html.twig'  with {'items': items} %}
        include __DIR__ . '/components/location-items.php';
    }

    if(!array_key_exists('item', $templateData) && !array_key_exists('items', $templateData)) {
        echo 'Keine buchbaren Items an Location';
    }
?>
