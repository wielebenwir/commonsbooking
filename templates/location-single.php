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
$templateData = \CommonsBooking\View\location::getTemplateData(); //@TODO: Clean up templatedata array 

$timeframes 	= $location->getBookableTimeframes();
$noResultText = __("This item is currently not available.", "commonsbooking");

$item_selected = isset($_GET['item']); //passed by timeframe-withitem or timeframe-withlocation
?>

<?php 
  set_query_var( 'location', $location );
  cb_get_template_part( 'location', 'single-meta' ); // file: location-single-meta.php 

  // booking calendar or timeframes list
  if ( $timeframes ) { 
    if ( $item_selected ) { // item selected, so we display the booking calendar 
        set_query_var( 'templateData', $templateData );
        cb_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php 
    } elseif ( ! $item_selected  ) {  // no item selected, so show a list of timeframes
      foreach ($timeframes as $timeframe ) { 
        set_query_var( 'timeframe', $timeframe );
        cb_get_template_part( 'timeframe', 'withitem' ); // file: timeframe-widthitem.php
      }  // end foreach $timeframes
    } // $item_selected 
  } else { // no timeframe ?>
		<div class="cb-status cb-availability-status cb-no-residency"><?php echo ( $noResultText ); ?>
<?php } // end if ($timeframes) ?>

