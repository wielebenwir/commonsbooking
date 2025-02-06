<?php
/**
 * Template loader
 *
 * based on https://github.com/WPBP/template/blob/master/template.php
 */

if ( ! function_exists( 'commonsbooking_get_template_part' ) ) {
	/**
	 * Load template files of the plugin also include a filter pn_get_template_part<br>
	 * Based on WooCommerce function<br>
	 *
	 * @param string $slug
	 * @param string $name
	 * @param bool   $include
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	function commonsbooking_get_template_part( $slug, $name = '', $include = true, $before = '', $after = '' ) {
		$template    = '';
		$plugin_slug = COMMONSBOOKING_PLUGIN_SLUG . '/';
		$path        = COMMONSBOOKING_PLUGIN_DIR . 'templates/';
		$class       = array();

		// Look in yourtheme/slug-name.php and yourtheme/plugin-name/slug-name.php
		if ( $name ) {
			$template = locate_template( array( "{$slug}-{$name}.php", $plugin_slug . "{$slug}-{$name}.php" ) );
		} else {
			$template = locate_template( array( "{$slug}.php", $plugin_slug . "{$slug}.php" ) );
		}

		// Get default slug-name.php
		if ( ! $template ) {
			if ( empty( $name ) ) {
				if ( file_exists( $path . "{$slug}.php" ) ) {
					$template = $path . "{$slug}.php";
				}
			} elseif ( file_exists( $path . "{$slug}-{$name}.php" ) ) {
				$template = $path . "{$slug}-{$name}.php";
			}
		}

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/plugin-name/slug.php
		if ( ! $template ) {
			$template = locate_template( array( "{$slug}.php", $plugin_slug . "{$slug}.php" ) );
		}

		// Allow 3rd party plugin filter template file from their plugin
		$template = apply_filters( 'commonsbooking_get_template_part', $template, $slug, $name, $plugin_slug );

		$has_post_thumbnail = ( has_post_thumbnail() ) ? 'has-post-thumbnail' : 'no-post-thumbnail'; // @TODO this feils because we have no global post anymore

		$template_classes = array(
			'cb-' . $slug . '-' . $name,
			'template-' . basename( $template, '.php' ),
			'post-' . get_post_type(), // @TODO: this returns "page", not the type (e.g. "item") that is queried.
			$has_post_thumbnail,
		);

		$css_classes = implode( ' ', $template_classes );

		// Add CB content wrapper & classes
		$before_html = ( $before != '' ) ? $before : '<div class="cb-wrapper ' . $css_classes . '">';
		$after_html  = ( $after != '' ) ? $after : '</div>';

		// Display debug message
		if ( WP_DEBUG ) {
			if ( empty( $template ) ) {
				$before_html .= ( '<div class="cb-debug">Template file not found</div>' );
			} else {
				$before_html .= ( '<div class="cb-debug">Template:<strong>' . basename( $template ) . '</strong></div>' );
			}
		}

		if ( $template && $include === true ) {
			echo( commonsbooking_sanitizeHTML( $before_html ) );
			load_template( $template, false );
			echo( commonsbooking_sanitizeHTML( $after_html ) );
		} elseif ( $template && $include === false ) {
			return $before_html . $template . $after_html;
		}
		return '';
	}
}
