<?php

namespace CommonsBooking\Settings;

use CommonsBooking\Wordpress\Options\AdminOptions;
use function get_option;

/**
 * Settings class
 *
 * The Options are accessible via the settings page and saved in the wp options table.
 * Each setting has a unique options_key and a field_id.
 * Both are needed to write and read the option from the options table.
 *
 * The options are defined in the \includes\OptionsArray.php file
 */
class Settings {

	/**
	 * GetOption
	 *
	 * Retrieves a single value from the options table based on options_key and field_id.
	 *
	 * @param mixed $options_key
	 * @param mixed $field_id
	 * @param mixed $sanitizeFunction Optional. Function to sanitize return value. If an array, each element
	 *                                 is sanitized recursively. Use false to not sanitize.
	 *                                 Default: 'commonsbooking_sanitizeHTML'.
	 * @param bool  $fallbackToDefault Optional. If true, and the option is not found, the default value will be returned.
	 *                                  Default: false.
	 *
	 * @return mixed
	 */
	public static function getOption( $options_key, $field_id, $sanitizeFunction = 'commonsbooking_sanitizeHTML', bool $fallbackToDefault = false ) {
		$cb_options_array = get_option( $options_key );

		if ( is_array( $cb_options_array ) && array_key_exists( $field_id, $cb_options_array ) ) {
			$optionValue = $cb_options_array[ $field_id ];
			if ( is_callable( $sanitizeFunction ) ) {
				return map_deep( $optionValue, $sanitizeFunction );
			} else {
				return $optionValue;
			}
		} else {
			if ( $fallbackToDefault ) {
				$defaultValue = self::getDefaultOption( $options_key, $field_id );
				return $defaultValue !== false && is_callable( $sanitizeFunction )
					? map_deep( $defaultValue, $sanitizeFunction )
					: $defaultValue;
			}
			return false;
		}
	}

	/**
	 * Gets the default value, can be used when an option is not yet set.
	 *
	 * @param string $options_key
	 * @param string $field_id
	 * @return string | false
	 */
	public static function getDefaultOption( string $options_key, string $field_id ): bool|string {
		$options_array = include COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php';
		$tab           = str_replace( AdminOptions::OPTION_KEY . '_', '', $options_key );
		if ( isset( $options_array[ $tab ] ) ) {
			$groups = $options_array[ $tab ]['field_groups'];
			foreach ( $groups as $group ) {
				$fields = $group['fields'];
				foreach ( $fields as $field ) {
					if ( isset( $field['id'] ) && $field['id'] === $field_id && isset( $field['default'] ) ) {
						return $field['default'];
					}
				}
			}
		}
		return false;
	}


	/**
	 * Updates a single field in a multidimensional options-array in wp_options
	 * Will create the option if it does not exist yet.
	 *
	 * @param string $option_name the options name as defined in wp_options table, column option_name
	 * @param mixed  $field_id the field_id in the array
	 * @param mixed  $field_value the new value
	 *
	 * @return bool
	 */
	public static function updateOption( $option_name, $field_id, $field_value ) {
		// Load all the option values from wp_options
		$options = get_option( $option_name );

		// if the option does not exist, we need to create an empty
		// array instead because we cannot convert false to an array
		if ( $options === false ) {
			$options = array();
		}

		// Update just the specific field
		$options[ $field_id ] = $field_value;

		// Save to wp_options
		return update_option( $option_name, $options );
	}


	/**
	 * @param string $postType
	 *
	 * @return string
	 */
	public static function returnFormattedMetaboxFields( string $postType ): string {
		$metabox_array = self::getOption( 'commonsbooking_settings_metaboxfields', $postType );

		$result = '<br>';
		if ( is_array( $metabox_array ) ) {
			foreach ( $metabox_array as $metabox_id => $metabox_name ) {
				$result .= $metabox_name . ' => [' . $metabox_id . '] <br>';
			}
		}

		return $result;
	}
}
