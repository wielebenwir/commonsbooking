<?php

function commonsbooking_admin() {
	// jQuery
	wp_enqueue_script( 'jquery' );

	// Datepicker extension
	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

	// Tooltip extension
	wp_enqueue_script( 'jquery-ui-tooltip', array( 'jquery' ) );

	// Sortable extension
	wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );

	// Pivottable
	wp_enqueue_script( 'admin-scripts-pivottable', COMMONSBOOKING_PLUGIN_URL . 'node_modules/pivottable/dist/pivot.js', array(), COMMONSBOOKING_VERSION );
	wp_enqueue_style( 'admin-styles-pivottable', COMMONSBOOKING_PLUGIN_URL . 'node_modules/pivottable/dist/pivot.css', array(), COMMONSBOOKING_VERSION );

	// Admin styles/scripts
	wp_enqueue_style( 'admin-styles', COMMONSBOOKING_PLUGIN_ASSETS_URL . 'admin/css/admin.css', array(), COMMONSBOOKING_VERSION );
	wp_enqueue_script( 'cb-scripts-admin', COMMONSBOOKING_PLUGIN_ASSETS_URL . 'admin/js/admin.js', array() );

	// Map marker upload scripts
    // TODO needs to be evaluated. Maybe not working on all systems
    if (get_current_screen()->id == 'cb_map') {
        $script_path = COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-marker-upload.js';
        wp_enqueue_script('cb-map-marker-upload_js', $script_path);
    }

	// CB 0.X migration
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_start_migration',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'start_migration' ),
		)
	);

	// CB 2 bookings migration - from timeframe to separate cpt
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_start_booking_migration',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'start_booking_migration' ),
		)
	);
	/**
	 * Ajax - cache warmup
	 */
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_cache_warmup',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cache_warmup' ),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'commonsbooking_admin' );

/**
 * commonsbooking_sanitizeHTML
 * Filters text content and strips out disallowed HTML.
 *
 * @param mixed $string
 *
 * @return string
 */
function commonsbooking_sanitizeHTML( $string ): string {
	global $allowedposttags;

	$allowed_atts = array(
		'align'      => array(),
		'checked'	 => array(),
		'class'      => array(),
		'type'       => array(),
		'id'         => array(),
		'dir'        => array(),
		'lang'       => array(),
		'style'      => array(),
		'xml:lang'   => array(),
		'src'        => array(),
		'alt'        => array(),
		'href'       => array(),
		'rel'        => array(),
		'rev'        => array(),
		'target'     => array(),
		'novalidate' => array(),
		'value'      => array(),
		'name'       => array(),
		'tabindex'   => array(),
		'action'     => array(),
		'method'     => array(),
		'for'        => array(),
		'width'      => array(),
		'height'     => array(),
		'data'       => array(),
		'title'      => array(),
	);

	$allowedposttags['form']     = $allowed_atts;
	$allowedposttags['label']    = $allowed_atts;
	$allowedposttags['input']    = $allowed_atts;
	$allowedposttags['textarea'] = $allowed_atts;
	$allowedposttags['iframe']   = $allowed_atts;
	$allowedposttags['script']   = $allowed_atts;
	$allowedposttags['style']    = $allowed_atts;
	$allowedposttags['strong']   = $allowed_atts;
	$allowedposttags['small']    = $allowed_atts;
	$allowedposttags['table']    = $allowed_atts;
	$allowedposttags['span']     = $allowed_atts;
	$allowedposttags['abbr']     = $allowed_atts;
	$allowedposttags['code']     = $allowed_atts;
	$allowedposttags['pre']      = $allowed_atts;
	$allowedposttags['div']      = $allowed_atts;
	$allowedposttags['img']      = $allowed_atts;
	$allowedposttags['h1']       = $allowed_atts;
	$allowedposttags['h2']       = $allowed_atts;
	$allowedposttags['h3']       = $allowed_atts;
	$allowedposttags['h4']       = $allowed_atts;
	$allowedposttags['h5']       = $allowed_atts;
	$allowedposttags['h6']       = $allowed_atts;
	$allowedposttags['ol']       = $allowed_atts;
	$allowedposttags['ul']       = $allowed_atts;
	$allowedposttags['li']       = $allowed_atts;
	$allowedposttags['em']       = $allowed_atts;
	$allowedposttags['hr']       = $allowed_atts;
	$allowedposttags['br']       = $allowed_atts;
	$allowedposttags['tr']       = $allowed_atts;
	$allowedposttags['td']       = $allowed_atts;
	$allowedposttags['p']        = $allowed_atts;
	$allowedposttags['a']        = $allowed_atts;
	$allowedposttags['b']        = $allowed_atts;
	$allowedposttags['i']        = $allowed_atts;
	$allowedposttags['select']        = $allowed_atts;
	$allowedposttags['option']        = $allowed_atts;

	return wp_kses( $string, $allowedposttags );
}

/**
 * Recursive sanitation for text or array
 *
 * @param mixed array_or_string (array|string)
 * @param string $sanitize_function name of the sanitziation function, default = sanitize_text_field. You can use any method that accepts a string as parameter
 *
 * See more wordpress sanitization functions: https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/
 *
 *
 * @return array|string
 */
function commonsbooking_sanitizeArrayorString( $array_or_string, $sanitize_function = 'sanitize_text_field' ) {
	if ( is_string( $array_or_string ) ) {
		$array_or_string = $sanitize_function( $array_or_string );
	} elseif ( is_array( $array_or_string ) ) {
		foreach ( $array_or_string as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = commonsbooking_sanitizeArrayorString( $value, $sanitize_function );
			} else {
				$value = commonsbooking_sanitizeArrayorString( $value, $sanitize_function );
			}
		}
	}

	return $array_or_string;
}