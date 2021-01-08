<?php
namespace cb_map;

/**
* returns the directory path of the given plugin main file relative to the plugin directory,
* i.e. commons-booking/commons-booking.php for $plugin_name = commons-booking.php
**/
function get_active_plugin_directory($plugin_name) {
  $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
  foreach($active_plugins as $plugin) {
      $plugin_file_path = CB_MAP_PATH . '../' . $plugin;
      if(strpos($plugin, $plugin_name) !== false && file_exists($plugin_file_path)) {
          return dirname($plugin);
      }
  }
  return null;
}

?>
