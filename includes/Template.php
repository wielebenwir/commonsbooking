<?php
/**
 * Template loader
 * 
 * based on https://github.com/WPBP/template/blob/master/template.php
 */

if ( !function_exists( 'cb_get_template_part' ) ) {
    /**
     * Load template files of the plugin also include a filter pn_get_template_part<br>
     * Based on WooCommerce function<br>
     * 
     * @param string $slug
     * @param string $name
     * @param bool   $include
     * @param string $before 
     * @param string $after 
     * @return string
     */
    function cb_get_template_part( $slug, $name = '', $include = true, $before = '', $after = '' ) {
      $template = '';
      $plugin_slug = CB_PLUGIN_SLUG . '/';
      $path = CB_PLUGIN_DIR . 'templates/';

      // Add CB content wrapper
      $before_html  = ! empty( $before )  ? $before : '<div class="cb-content">';  
      $after_html   = ! empty( $after )   ? $after  : '</div>';  
      
      // Look in yourtheme/slug-name.php and yourtheme/plugin-name/slug-name.php
      if ( $name ) {
        $template = locate_template( array( "{$slug}-{$name}.php", $plugin_slug . "{$slug}-{$name}.php" ) );
      } else {
        $template = locate_template( array( "{$slug}.php", $plugin_slug . "{$slug}.php" ) );
      }

      // Get default slug-name.php
      if ( !$template ) {
          if ( empty( $name ) ) {
            if ( file_exists( $path . "{$slug}.php" ) ) {
              $template = $path . "{$slug}.php";
            }
          } else if ( file_exists( $path . "{$slug}-{$name}.php" ) ) {
              $template = $path . "{$slug}-{$name}.php";
          }
      }

      // If template file doesn't exist, look in yourtheme/slug.php and yourtheme/plugin-name/slug.php
      if ( !$template ) {
        $template = locate_template( array( "{$slug}.php", $plugin_slug . "{$slug}.php" ) );
      }

      // Allow 3rd party plugin filter template file from their plugin
      $template = apply_filters( 'cb_get_template_part', $template, $slug, $name, $plugin_slug );

      // Display debug message
      if ( WP_DEBUG ) { 
        if ( empty ( $template ) ) {
          echo ( '<div class="cb-debug">Template file not found</div>' );
        } else {
          echo ( '<div class="cb-debug">Using template:' . $template . '</div>' );
        }
      }

      if ( $template && $include === true ) {
        echo ( $before_html );
        load_template( $template, false );
        echo ( $after_html );
      } else if ( $template && $include === false ) {
        return $before_html . $template . $after_html;
      }
    }
}
