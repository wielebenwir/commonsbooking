<?php
    /**
     * Single item calendar with booking functionality
     *
     * Used on item single
     */
    $templateData = \CommonsBooking\View\Item::getTemplateData();
?>

<?php
    $post = $templateData['post'];

    // Single Item View
    if(array_key_exists('location', $templateData) && $templateData['location']) {
        include __DIR__ . '/components/item-location.php';
    }

    // Multi Item View
    if(array_key_exists('locations', $templateData) && $templateData['locations']) {
        include __DIR__ . '/components/item-locations.php';
    }

    if(!array_key_exists('location', $templateData) && !array_key_exists('locations', $templateData)) {
        echo 'Keine buchbaren Locations an Item';
    }

?>
