<?php


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

        ?><div class="cmb-row cmb-type-text "><?php

        if($startMigration) {
            $results = \CommonsBooking\Migration\Migration::migrateAll();
            foreach ($results as $type => $count) {
                echo "$count $type updated/saved.<br>";
            }
        }
        ?>
            <br>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php')); ?>?page=commonsbooking_options_migration&migration=true">Start Migration</a>
            </div>
        <?php
    }

}
