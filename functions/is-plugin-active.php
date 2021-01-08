<?php
namespace cb_map;

function is_plugin_active($plugin_name) {
  $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

  foreach($active_plugins as $plugin) {
    $plugin_file_path = CB_MAP_PATH . '../' . $plugin;
    if(strpos($plugin, $plugin_name) !== false && file_exists($plugin_file_path)){
        return true;
    }
  }

  return false;
}

?>
