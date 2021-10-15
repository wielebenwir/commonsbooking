<?php

namespace CommonsBooking\Settings;

use function get_option;

/**
 * Settings
 *
 *
 */
class Settings {

	/**
	 * array_flatten
	 * Flattens a multidimensional array to get $key->value into a single dimension.
	 *
	 * @param mixed $array
     * @param string|bool $parent
     * @return array|bool
     */
	static function flattenArray( $array , $parent = false) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		$result = array();

		foreach ( $array as $key => $value ) {
			if($parent === false) {
			if ( is_array( $value ) ) {
                    $result = array_merge($result, self::flattenArray($value, $key));
			} else {
				$result[ $key ] = $value;
			}
            } else {
                $result[$parent][$key] = $value;
            }

		}

		return $result;
	}

	/**
	 * GetOption
	 *
	 * Retrieves a single value from the options table based on the options key and field_id
	 *
	 * @param $options_key
	 * @param $field_id
	 *
     * @return array
	 */
	public static function getOption( $options_key, $field_id ) {
		$cb_options_array = get_option( $options_key );

		// as multiple values can be  stored as an multidimensional array we need to flatten the array into one dimensional array
		$flat_array = self::flattenArray( $cb_options_array );

		if ( is_array( $cb_options_array ) && array_key_exists( $field_id, $flat_array ) ) {
			$result = $flat_array[ $field_id ];
		} else {
			$result = false;
		}

		return $result;
	}


	/**
	 * Updates a single field in a multidimensional options-array in wp_options
	 * re
	 *
	 * @param mixed $option_name the options name as defined in wp_options table, column option_name
	 * @param mixed $field_id the field_id in the array
	 * @param mixed $field_value the new value
	 *
	 * @return true
	 */
	public static function updateOption( $option_name, $field_id, $field_value ) {
		// Load all of the option values from wp_options
		$options = get_option( $option_name );

		// Update just the specific field
		$options[ $field_id ] = $field_value;

		// Save to wp_options
		return update_option( $option_name, $options );

	}
}
