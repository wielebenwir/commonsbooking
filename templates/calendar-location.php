<?php
    /**
     * Single item calendar with booking functionality
     *
     * Used on item single
     */
    $templateData = \CommonsBooking\View\Location::getTemplateData();
?>

<h2>Buchungskalender</h2>
<?php
    $post = $templateData['post'];

    # Single Item View
    if(array_key_exists('item', $templateData) && $templateData['item']) {
        include __DIR__ . '/components/location-item.php';
    }

    # Multi Item View
    if(array_key_exists('items', $templateData) && $templateData['items']) {
        include __DIR__ . '/components/location-items.php';
    }

    if(!array_key_exists('item', $templateData) && !array_key_exists('items', $templateData)) {
        echo 'Keine buchbaren Items an Location';
    }

?>
