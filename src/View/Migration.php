<?php

namespace CommonsBooking\View;


class Migration
{
    /**
     * Render Migration Form.
     *
     * @param array $field_args Array of field arguments.
     * @param CMB2_Field $field The field object
     */
    public static function renderMigrationForm($field_args, $field)
    {
        $cb1Installed = \CommonsBooking\Repository\CB1::isInstalled();

        ?>
        <div class="cmb-row cmb-type-text "><?php

        if ( ! $cb1Installed) {
            echo '<strong style="color:red">' . esc_html__('We could not detect a version of an older CommonsBooking Installation (Version 0.X).',
                    'commonsbooking') . '</strong>';
        } else {
            echo '<strong style="color: green">' . esc_html__('Found a version of an older CommonsBooking Installation (Version 0.X). You can migrate.',
                    'commonsbooking') . '</strong>';
        }
        echo('
            <br><br>
            
            <div id="migration-state" style="display: none;">
                <strong style="color: red">' . esc_html__('migration in process .. please wait ...', 'commonsbooking') . '</strong><br><br>
                <span id="locations-count">0</span> ' . esc_html__(' Locations updated/saved', 'commonsbooking') . '<br>
                <span id="items-count">0</span>' . esc_html__(' Items updated/saved', 'commonsbooking') . '<br>
                <span id="timeframes-count">0</span>' . esc_html__(' Timeframes updated/saved', 'commonsbooking') . '<br>
                <span id="bookings-count">0</span>' . esc_html__(' Bookings updated/saved', 'commonsbooking') . '<br>
                <span id="bookingCodes-count">0</span>' . esc_html__(' Booking Codes updated/saved', 'commonsbooking') . '<br>
                <span id="termsUrl-count">0</span>' . esc_html__(' Terms & Urls updated/saved', 'commonsbooking') . '<br>
                <span id="taxonomies-count">0</span>' . esc_html__(' Taxonomies updated/saved', 'commonsbooking') . '<br>
                <span id="options-count">0</span>' . esc_html__(' Options updated/saved', 'commonsbooking') . '<br>
            </div>
            
            <div id="migration-done" style="display: none;">
                <br><strong style="color: green">' . esc_html__('Migration finished', 'commonsbooking') . '</strong><br><br>
            </div>
            
        ');


        ?>
        <br>

        <?php 
        if ($cb1Installed) { 
        ?>
        <a id="migration-start" class="button button-primary"
           href="<?php echo esc_url(admin_url('admin.php')); ?>?page=commonsbooking_options_migration&migration=true"> <?php echo esc_html__('Start Migration',
                'commonsbooking'); ?></a>
        <?php
        } // end if cb1installed
        ?>
        </div>
        <?php
    }

}
