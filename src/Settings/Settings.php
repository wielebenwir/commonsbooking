<?php

namespace CommonsBooking\Settings;

use function get_option;

/**
 * Settings class
 *
 * The Options are determined in the settings page and saved in the options table.
 * Each setting has a unique key and a field_id.
 * Both are needed to write and read the option from the options table.
 *
 * The options are determined in the \includes\OptionsArray.php file
 */
class Settings {

	/**
	 * GetOption
	 *
	 * Retrieves a single value from the options table based on the options key and field_id
	 *
	 * @param $options_key
	 * @param $field_id
	 * @param $sanitizeFunction Optional. Function to sanitize return value (if array: each element recursively). Default: 'commonsbooking_sanitizeHTML'. Use false to not sanitize.
	 *
	 * @return mixed
	 */
	public static function getOption( $options_key, $field_id, $sanitizeFunction = 'commonsbooking_sanitizeHTML' ) {
		$cb_options_array = get_option( $options_key );

		if ( is_array( $cb_options_array ) && array_key_exists( $field_id, $cb_options_array ) ) {
			$optionValue = $cb_options_array[ $field_id ];
			if (function_exists($sanitizeFunction)) {
				return map_deep($optionValue, $sanitizeFunction);
			} else {
				return $optionValue;
			}
		} else {
			return false;
		}

	}


	/**
	 * Updates a single field in a multidimensional options-array in wp_options
	 * Will create the option if it does not exist yet.
	 *
	 *
	 * @param mixed $option_name the options name as defined in wp_options table, column option_name
	 * @param mixed $field_id the field_id in the array
	 * @param mixed $field_value the new value
	 *
	 * @return true
	 */
	public static function updateOption( $option_name, $field_id, $field_value ) {
		// Load all the option values from wp_options
		$options = get_option( $option_name );

		//if the option does not exist, we need to create an empty
		//array instead because we cannot convert false to an array
		if ( $options === false ) {
			$options = array();
		}

		// Update just the specific field
		$options[ $field_id ] = $field_value;

		// Save to wp_options
		return update_option( $option_name, $options );

	}


	public static function returnFormattedMetaboxFields( $postType ) {
		$metabox_array = Settings::getOption('commonsbooking_settings_metaboxfields', $postType);

		$result = "<br>";
		if(is_array($metabox_array)) {
			foreach ( $metabox_array as $metabox_id => $metabox_name ) {
				$result .= $metabox_name . ' => [' . $metabox_id . '] <br>';
			}
		}

		return $result;
	}

}
