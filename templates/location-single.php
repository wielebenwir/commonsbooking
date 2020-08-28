<?php 
/** 
* Single location with either list of timeframes or booking calendar
* 
* Original post content is preserved, contents of this file are attached.
* 
* WP Post properties for location are available as $location->property
* location Model methods are available as $location->myMethod()   
* 
* Model: Location
*/
//$templateData = \CommonsBooking\View\location::getTemplateData();

$titleLink 		= sprintf('<h2><a href="%s">%s</a></h2>', get_the_permalink($location->ID), $location->post_title );
$thumbnail 		= ( has_post_thumbnail($location->ID) ) ? get_the_post_thumbnail($location->ID) : '';
$timeframes 	= $location->getBookableTimeframes();
$noResultText = __("This item is currently not available.", "commonsbooking");

$item_selected = isset($_GET['item']);
?>

<?php 
  if ( $timeframes ) { 
    if ( $item_selected ) { // item selected + has timeframes
        set_query_var( 'timeframe', $timeframes[0] );
        cb_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php 
    } elseif ( ! $item_selected  ) {  // no item selected+ has timeframes
      foreach ($timeframes as $timeframe ) { 
        set_query_var( 'timeframe', $timeframe );
        cb_get_template_part( 'timeframe', 'withitem' ); // file: timeframe-widthitem.php
      } 
    } // $item_selected 
  } else {
    ?>
		<div class="cb-status cb-availability-status cb-no-residency"><?php echo ( $noResultText ); ?>
<?php } // end if ($timeframes) ?>




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
