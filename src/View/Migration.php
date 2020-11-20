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
            echo '<strong style="color:red">' . __('We could not detect a version of CommonsBooking 1 (Version 0.X).',
                    'commonsbooking') . '</strong>';
        } else {
            echo '<strong style="color:green">' . __('Found a version of CommonsBooking 1 (Version 0.X). You can migrate.',
                    'commonsbooking') . '</strong>';
        }
        echo('
            <br><br>
            
            <div id="migration-state" style="display: none;">
                <strong style="animation: blinker 0.6s linear infinite">migration in process .. please wait ... </strong><br><br>
                <span id="locations-count">0</span> Locations ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="items-count">0</span> Items ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="timeframes-count">0</span> Timeframes ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="bookings-count">0</span> Bookings ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="bookingCodes-count">0</span> BookingCodes ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="termsUrl-count">0</span> TermsUrls ' . __(' updated/saved', 'commonsbooking') . '<br>
                <span id="taxonomies-count">0</span> Taxonomies ' . __(' updated/saved', 'commonsbooking') . '<br>
            </div>
            
            <div id="migration-done" style="display: none;">
                <br><strong style="color: green">Migration finished</strong><br><br>
            </div>
            
        ');


        ?>
        <br>
        <a id="migration-start" class="button button-primary"
           href="<?php echo esc_url(admin_url('admin.php')); ?>?page=commonsbooking_options_migration&migration=true"> <?php echo __('Start Migration',
                'commonsbooking'); ?></a>
        </div>
        <?php
    }

}
