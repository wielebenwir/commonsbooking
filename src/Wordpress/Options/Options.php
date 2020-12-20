<?php

namespace CommonsBooking\Wordpress\Options;

use CommonsBooking\Settings\Settings;

class Options
{
    private static $option_key = COMMONSBOOKING_PLUGIN_SLUG . '_options';
    
    /**
     * set default values to admin options field as defined in includes/Optionsp.php
     *
     * @return void
     */
    public static function SetOptionsDefaultValues() {

        include(COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php');        
        foreach ($options_array as $tab_id => $tab) {

            $groups = $tab['field_groups'];
            
            foreach ($groups as $group_id => $group) {

                $fields = $group['fields'];
                $option_key = self::$option_key . '_' . $tab_id;
                $option = array();
                
                foreach ($fields as $field) {
                    
                    // we check if there there is a default value for this field
                    if (array_key_exists( 'default', $field ) ) {
                        // if field-value is not set already we add the default value to the options array
                        if ( empty ( Settings::getOption($option_key, $field['id'] ) ) ) {
                            $option[$field['id']] = $field['default'];
                        }
                    }
                }
    
                // update option 
                if (!empty ( $option ) ) {
                    update_option($option_key, $option);
                }
            }

        }
    }


}