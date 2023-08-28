<?php

/**
 * Plugin Name:         Commons Booking
 * Version:             2.8.3
 * Requires at least:   5.2
 * Requires PHP:        7.4
 * Plugin URI:          https://commonsbooking.org
 * Description:         A wordpress plugin for management and booking of common goods.
 * Author:              wielebenwir e.V.
 * Author URI:          https://wielebenwir.de/
 * Domain Path:         /languages
 * Text Domain:         commonsbooking
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 */

use CommonsBooking\Plugin;

defined('ABSPATH') or die("Thanks for visting");
define('COMMONSBOOKING_VERSION', '2.8.3');
define('COMMONSBOOKING_PLUGIN_SLUG', 'commonsbooking');
define('COMMONSBOOKING_MENU_SLUG', COMMONSBOOKING_PLUGIN_SLUG . '-menu');
define('COMMONSBOOKING_PLUGIN_DIR', wp_normalize_path( plugin_dir_path(__FILE__)));
define('COMMONSBOOKING_PLUGIN_URL', plugins_url('/', __FILE__));
define('COMMONSBOOKING__FILE__', __FILE__ );
define('COMMONSBOOKING_PLUGIN_BASE', plugin_basename( COMMONSBOOKING__FILE__ ) );
define('COMMONSBOOKING_PLUGIN_ASSETS_URL', plugins_url( 'assets/', __FILE__ ));
define('COMMONSBOOKING_PLUGIN_FILE', __FILE__);
define('COMMONSBOOKING_METABOX_PREFIX', '_cb_'); //Start with an underscore to hide fields from custom fields list

define( 'COMMONSBOOKING_MAP_PATH', wp_normalize_path( plugin_dir_path( __FILE__ ) ));
define( 'COMMONSBOOKING_MAP_ASSETS_URL', plugins_url( 'assets/map/', __FILE__ ));
define( 'COMMONSBOOKING_MAP_LANG_PATH', dirname( plugin_basename( __FILE__ )) . '/languages/' );
define ('COMMONSBOOKING_MAP_PLUGIN_DATA', get_file_data( __FILE__, array('Version' => 'Version'), false));

global $cb_db_version;
$cb_db_version = '1.0';

require __DIR__. '/includes/Admin.php';
require __DIR__. '/includes/Public.php';
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/vendor/cmb2/cmb2/init.php';
require __DIR__.'/vendor/mustardBees/cmb-field-select2/cmb-field-select2.php';
require_once __DIR__. '/includes/Plugin.php';
