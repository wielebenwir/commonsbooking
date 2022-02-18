<?php

namespace CommonsBooking\Settings;

use function get_option;
use ScssPhp\ScssPhp\Compiler;

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
					$result[ $key ] = commonsbooking_sanitizeHTML($value);
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
     * @return mixed
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
	 * 
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

	public static function updateColors() {
		$compiler = new Compiler();
		$source_scss = COMMONSBOOKING_PLUGIN_DIR . 'assets/public/sass/public.scss';
		$import_path = COMMONSBOOKING_PLUGIN_DIR . 'assets/public/sass/';
		$compiler->setImportPaths($import_path);
		$target_css = COMMONSBOOKING_PLUGIN_DIR . 'assets/public/css/public.css';
		$target_sourcemap = COMMONSBOOKING_PLUGIN_DIR . 'assets/public/css/public.css.map';

		$variables = [
			'color-primary' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_primarycolor'),
			'color-secondary' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_secondarycolor'),
			'color-accept' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_acceptcolor'),
			'color-cancel' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_cancelcolor'),
			'color-holiday' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_holidaycolor'),
			'color-greyedout' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_greyedoutcolor'),
			'color-bg' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_backgroundcolor'),
			'color-noticebg' => Settings::getOption('commonsbooking_options_templates', 'colorscheme_noticebackgroundcolor'),
		];		
		$compiler->replaceVariables($variables);
		$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
		$compiler->setSourceMapOptions([
			// relative or full url to the above .map file
			'sourceMapURL' => $target_sourcemap,

			// (optional) relative or full url to the .css file
			'sourceMapFilename' => $source_scss,

			// partial path (server root) removed (normalized) to create a relative url
			'sourceMapBasepath' => '/var/www/vhost',

			// (optional) prepended to 'source' field entries for relocating source files
			'sourceRoot' => '/',
		]);
		$result = $compiler->compileString('@import "' . $source_scss . '";');
		$css = $result->getCss();
		$sourcemap = $result->getSourceMap();
		if (!empty($css) && is_string($css)) {
			file_put_contents($target_css, $css);
			file_put_contents($target_sourcemap,$sourcemap);
		}
	}
}
