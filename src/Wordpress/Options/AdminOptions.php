<?php

namespace CommonsBooking\Wordpress\Options;

use CommonsBooking\Messages\AdminMessage;
use CommonsBooking\Settings\Settings;

/**
 * AdminOptions
 * The options are saved in wp_options table.
 * The options are defined in includes/OptionsArray.php
 */
class AdminOptions {
	private static $option_key = COMMONSBOOKING_PLUGIN_SLUG . '_options';

	/**
	 * set default values to admin options fields as defined in includes/OptionsArray.php
	 *
	 * @return void
	 */
	public static function setOptionsDefaultValues() {

		$options_array   = include COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php';
		$restored_fields = array();

		foreach ( $options_array as $tab_id => $tab ) {
			$groups     = $tab['field_groups'];
			$option_key = self::$option_key . '_' . $tab_id;

			foreach ( $groups as $group ) {
				$fields = $group['fields'];

				foreach ( $fields as $field ) {
					$field_id = $field['id'];

					// set to current value from wp_options
					$field_value = Settings::getOption( $option_key, $field_id );

					// we check if there is a default value set in OptionsArray.php and if the field type is not checkbox (cause checkboxes have empty values if unchecked )
					if ( array_key_exists( 'default', $field ) && $field['default'] !== '0' && $field['type'] != 'checkbox' ) {
						// if field-value is not set already we add the default value to the options array
						if ( empty( $field_value ) ) {
							Settings::updateOption( $option_key, $field_id, $field['default'] );
							$restored_fields[] = $field['name'];
						}
					}
				}
			}
		}

		// maybe show admin notice if fields are restored to their default value
		if ( ! empty( $restored_fields ) ) {
			$message  = commonsbooking_sanitizeHTML( __( '<strong>Default values for following fields automatically set or restored, because they were empty:</strong><br> ', 'commonsbooking' ) );
			$message .= implode( '<br> ', $restored_fields );
			new AdminMessage( $message );
		}
	}
}
