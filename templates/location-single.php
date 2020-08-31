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

$item_is_selected = isset($_GET['item']); //passed by timeframe-withitem or timeframe-withlocation
$item_id          = $item_is_selected ? $_GET['item'] : FALSE;
?>

<?php 
  set_query_var( 'location', $location );
  cb_get_template_part( 'location', 'single-meta' ); // file: location-single-meta.php 

  // booking calendar or timeframes list
  if ( $timeframes ) { 
    if ( $item_is_selected ) { // item selected, so we display the booking calendar 
      ?>
      <h2><?php echo __( 'Book this item', 'commonsbooking'); ?></h2>
      
      <?php 
      /**
       * @TODO: Mega stupid workaround, but the $templateData does not contain the item object?
       * 
       * If we do not have multiple calendars on one page, 
       * we could book from a timeframe-single page (neither item or location page, 
       * but a seperate one with the most important information about the item+location). 
       * 
       * Otherwise, at least book from the item page.
       *  
       */
      $item = get_post($item_id);
      $item = new \CommonsBooking\Model\Item($item);
      
      set_query_var( 'item', $item );
      cb_get_template_part( 'item', 'calendar-header' ); // file: item-calendar-header.php 
      /** end stupid workaround */
      
      set_query_var( 'templateData', $templateData );
      cb_get_template_part( 'timeframe', 'calendar' ); // file: timeframe-calendar.php 

    } elseif ( ! $item_is_selected  ) {  // no item selected, so show a list of timeframes
      foreach ($timeframes as $timeframe ) { 
        set_query_var( 'timeframe', $timeframe );
        cb_get_template_part( 'timeframe', 'withitem' ); // file: timeframe-withitem.php
      }  // end foreach $timeframes
    } // $item_is_selected 
  } else { // no timeframe ?>
		<div class="cb-status cb-availability-status cb-no-residency"><?php echo ( $noResultText ); ?>
<?php } // end if ($timeframes) ?>

