<?php

namespace CommonsBooking\Settings;

class Settings
{

	public static $field_id;
	public static $cb_options_array;
	public static $options_name;
	public static $options_key;


	public function __construct()
	{
		//$this->options_name = $options_name;
	}

	/**
	 * array_flatten
	 * Flattens a multidimensional array to get $key->value into a single dimension.
	 *
	 * @param  mixed $array
	 * @return void
	 */
	static function flattenArray($array)
	{

		if (!is_array($array)) {
			return FALSE;
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
	 * @param  mixed $options_name
	 * @param  mixed $field
	 * @return void
	 */
	public static function getOption($options_key, $field_id)
	{
		self::$options_key = $options_key;
		self::$field_id = $field_id;		
	

		self::$cb_options_array = \get_option(self::$options_key);
		$flat_array = self::flattenArray(self::$cb_options_array);

		if (array_key_exists(self::$field_id, self::$cb_options_array)) {
			$result = $flat_array[self::$field_id];
		} else {
			$result = false;
		}
		return $result;
	}
}
