<?php

namespace CommonsBooking\Settings;

/**
 * Settings
 * 
 * 
 */
class Settings
{

	/**
	 * array_flatten
	 * Flattens a multidimensional array to get $key->value into a single dimension.
	 *
	 * @param  mixed $array
	 * @return array|bool
	 */
	static function flattenArray($array)
	{
		if (!is_array($array)) {
			return false;
		}
		$result = array();
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$result = array_merge($result, self::flattenArray($value));
			} else {
				$result[$key] = $value;
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
     * @return void
     */
	public static function getOption($options_key, $field_id)
	{
		$cb_options_array = \get_option($options_key);

		// as multiple values can be  stored as an multidimensional array we need to flatten the array into one dimensional array
		$flat_array = self::flattenArray($cb_options_array);

		if (is_array($cb_options_array) && array_key_exists($field_id, $cb_options_array)) {
			$result = $flat_array[$field_id];
		} else {
        $result = false;
		}

		return $result;
	}
}
