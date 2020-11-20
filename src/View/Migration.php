<?php
use CommonsBooking\Repository\CB1;

namespace CommonsBooking\View;


class Migration
{    
    /**
     * Render Migration Form.
     *
     * @param  array      $field_args Array of field arguments.
     * @param  CMB2_Field $field      The field object
     */
    public static function renderMigrationForm( $field_args, $field ) {
        $startMigration = array_key_exists('migration', $_GET) && $_GET['migration'] == "true";
        $cb1Installed = \CommonsBooking\Repository\CB1::isInstalled();
        
        ?><div class="cmb-row cmb-type-text "><?php
        
        if ( ! $cb1Installed ) { 
            echo '<strong style="color:red">' . __('We could not detect a version of CommonsBooking 1 (Version 0.X).', 'commonsbooking') . '</strong>'; 
        } else { 
            echo '<strong style="color:green">' . __('Found a version of CommonsBooking 1 (Version 0.X). You can migrate.', 'commonsbooking') . '</strong>'; 
        }
        echo ('<br><br>');

      
        if($startMigration) {

            echo '<strong style="animation: blinker 0.6s linear infinite">migration in process .. please wait ... </strong><br><br>';
            flush();

            $migrationTypes = array (
                'locations',
                'items',
                //'timeframes',
                //'bookings',
                'bookingCodes',
                'termsUrl',
                'taxonomies',
            );

            foreach ($migrationTypes AS $value) {
                $results = \CommonsBooking\Migration\Migration::migrateAll($value);
                echo $results[$value] . ' ' . $value . __(' updated/saved', 'commonsbooking') . '<br>';
                flush();
                //sleep(1);
            }

            echo '<br><strong style="color: green">Migration finished</strong><br><br>';
        }

        ?>
            <br>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php')); ?>?page=commonsbooking_options_migration&migration=true"> <?php echo __('Start Migration', 'commonsbooking'); ?></a>
            </div>
        <?php
    }

}
