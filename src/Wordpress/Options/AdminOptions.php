<?php

namespace CommonsBooking\Wordpress\Options;
use CommonsBooking\Settings\Settings;

/**
 * AdminOptions
 */
class AdminOptions
{
    private static $option_key = COMMONSBOOKING_PLUGIN_SLUG . '_options';

    /**
     * set default values to admin options fields as defined in includes/OptionsArray.php
     *
     * @return void
     */
    public static function setOptionsDefaultValues() {

        $options_array = include(COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php');
        $restored_fields = false;

        foreach ($options_array as $tab_id => $tab) {
            $groups = $tab['field_groups'];
            $option_key = self::$option_key . '_' . $tab_id;

            foreach ($groups as $group_id => $group) {
                $fields = $group['fields'];         

                foreach ($fields as $field) {

                    $field_id = $field['id'];

                    // set to current value from wp_options
                    $field_value = Settings::getOption( $option_key, $field_id );
                    
                    if (array_key_exists( 'default', $field ) ) {
                        // if field-value is not set already we add the default value to the options array
                        if ( empty ( $field_value ) ) {
                            Settings::updateOption($option_key, $field_id, $field['default']);
                            $restored_fields[] = $field['name'];
                        }
                    }
                }         
            }
        }

        // maybe show admin notice if fields are restored to hreir default value
        self::setDefaultsAdminNotice($restored_fields);
    }
    
    /**
     * Display admin notice if option fields are set to their default values
     *
     * @param  mixed $fields
     * @return void
     */
    public static function setDefaultsAdminNotice($fields = false) {

        if ($fields && is_array($fields)) {   

            ?>
                    <div class="notice notice-info is-dismissible">
                        <p><?php echo commonsbooking_sanitizeHTML('<strong>Default values for following fields automatically restored, because they were empty:</strong><br> ', 'commonsbooking'); 
                        echo implode("<br> ", $fields); ?></p>
                    </div>
            <?php 
        }   
    }
}
