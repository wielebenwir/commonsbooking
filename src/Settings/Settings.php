<?php

/**
 * loading the options.php needs to be replaced by wordpress standard implementatio
 */
// require_once(CB_PLUGIN_DIR . 'src/Settings/Options.php');


namespace CommonsBooking\Settings;

class Settings
{

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
	public static function getOption($options_name, $field)
	{
		$options_array = cmb2_get_option($options_name);
		$flat_array = self::flattenArray($options_array);

		if (array_key_exists($field, $flat_array)) {
			$result = $flat_array[$field];
		} else {
			$result = false;
		}
		return $result;
	}
}
