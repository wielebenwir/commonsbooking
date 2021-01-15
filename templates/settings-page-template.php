<?php

use CommonsBooking\Map\MapSettings;
use CommonsBooking\Wordpress\CustomPostType\Map;

?>
<div class="wrap">

  <h1><?php echo  commonsbooking_sanitizeHTML( __('Settings for Commons Booking Map', 'commonsbooking')); ?></h1>

  <p><?php echo  commonsbooking_sanitizeHTML( __('general settings regarding the behaviour of the Commons Booking Map plugin', 'commonsbooking')); ?></p>

  <form method="post" action="options.php">
    <?php
      settings_fields( 'cb-map-settings' );
      do_settings_sections( 'cb-map-settings' );
    ?>

    <table class="text-left">
      <tr>
          <th>
            <?php echo  commonsbooking_sanitizeHTML( __('replace map link on booking page', 'commonsbooking')); ?>:
            <span class="dashicons dashicons-editor-help" title="<?php echo  commonsbooking_sanitizeHTML( __('set the target of the map link on booking page to openstreetmap', 'commonsbooking')); ?>"></span>
          </th>
          <td>
            <input type="checkbox" name="cb_map_options[booking_page_link_replacement]" <?php echo  MapSettings::get_option('booking_page_link_replacement') ? 'checked="checked"' : '' ?> value="on">
          </td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>
</div>
