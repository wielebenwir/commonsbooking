<?php

function commonsbooking_admin() {
	// jQuery
	wp_enqueue_script( 'jquery' );

	// Datepicker extension
	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

	// Tooltip extension
	wp_enqueue_script( 'jquery-ui-tooltip', array( 'jquery' ) );

	wp_enqueue_style( 'admin-styles', COMMONSBOOKING_PLUGIN_ASSETS_URL . 'admin/css/admin.css', array(), COMMONSBOOKING_VERSION );

	// Scripts for the WordPress backend
	if ( WP_DEBUG ) {
		wp_enqueue_script(
			'cb-scripts-admin',
			COMMONSBOOKING_PLUGIN_ASSETS_URL . 'admin/js/admin.js',
			array(),
			time()
		);
	} else {
		wp_enqueue_script(
			'cb-scripts-admin',
			COMMONSBOOKING_PLUGIN_ASSETS_URL . 'admin/js/admin.min.js',
			array(),
			COMMONSBOOKING_VERSION
		);
	}

    // Map marker upload scripts
    // TODO needs to be evaluated. Maybe not working on all systems
    if ( get_current_screen()->id == 'cb_map' ) {
        $script_path = COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-marker-upload.js';
        wp_enqueue_script( 'cb-map-marker-upload_js', $script_path );
    }

	// CB 0.X migration
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_start_migration',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cb_start_migration' ),
		)
	);

	// CB 2 bookings migration - from timeframe to separate cpt
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_start_booking_migration',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cb_start_booking_migration' ),
		)
	);

	// \CommonsBooking\Service\Upgrade Ajax tasks
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_run_upgrade',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cb_run_upgrade' ),
		)
	);

	// Additional info for CMB2 to handle booking rules
	wp_add_inline_script(
		'cb-scripts-admin',
'cb_booking_rules=' . \CommonsBooking\Service\BookingRule::getRulesJSON() . ';'
		. 'cb_applied_booking_rules=' . \CommonsBooking\Service\BookingRuleApplied::getRulesJSON() . ';',
	);

	/**
	 * Ajax - cache warmup
	 */
	wp_localize_script(
		'cb-scripts-admin',
		'cb_ajax_cache_warmup',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cb_cache_warmup' ),
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

	if ( empty ( $string ) ) {
		return '';
	}
	$allowed_atts = array(
		'align'      => array(),
		'checked'    => array(),
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
		'cellspacing'      => array(),
		'cellpadding'      => array(),
		'border'      => array(),
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
	$allowedposttags['select']   = $allowed_atts;
	$allowedposttags['option']   = $allowed_atts;

	return wp_kses( $string, $allowedposttags );
}

/**
 * Create filter hooks for cmb2 fields
 *
 * @param array $field_args  Array of field args.
 *
 *
 * : https://cmb2.io/docs/field-parameters#-default_cb
 *
 * @return mixed
 */
function commonsbooking_filter_from_cmb2( $field_args ) {
	// Only return default value if we don't have a post ID (in the 'post' query variable)
	if ( isset( $_GET['post'] ) ) {
		// No default value.
		return '';
	} else {
		$filterName    = sprintf( 'commonsbooking_defaults_%s', $field_args['id'] );
		$default_value = array_key_exists( 'default_value', $field_args ) ? $field_args['default_value'] : '';
		return apply_filters( $filterName, $default_value );
	}
}

/**
 * Only return default value if we don't have a post ID (in the 'post' query variable)
 *
 * @param  bool  $default On/Off (true/false)
 * @return mixed          Returns true or '', the blank default
 */
function cmb2_set_checkbox_default_for_new_post() {
	return isset( $_GET['post'] )
		// No default value.
		? ''
		// Default to true.
		: true;
}

/**
 * Recursive sanitation for text or array
 *
 * @param mixed  array_or_string (array|string)
 * @param string $sanitize_function name of the sanitziation function, default = sanitize_text_field. You can use any method that accepts a string as parameter
 *
 * See more wordpress sanitization functions: https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/
 *
 * @return array|string
 */

function commonsbooking_sanitizeArrayorString( $data, $sanitizeFunction = 'sanitize_text_field'  ) {
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            $data[ $key ] = commonsbooking_sanitizeArrayorString( $value, $sanitizeFunction );
        }
    } else {
        $data = call_user_func( $sanitizeFunction, $data );
    }   
    
    return $data;

}


/**
 * writes messages to error_log file
 * only active if DEBUG_LOG is on
 *
 * @param mixed $log can be a string, array or object
 * @param bool $backtrace if set true the file-path and line of the calling file will be added to the error message
 *
 * @return string logmessage 
 */
function commonsbooking_write_log( $log, $backtrace = true ) {

    if ( ! WP_DEBUG_LOG ) {
        return;
    }

    if ( is_array( $log ) || is_object( $log ) ) {
		$logmessage = ( print_r( $log, true ) );
	} else {
		$logmessage =  $log ;
	}

	if ( $backtrace ) {
		$bt   = debug_backtrace();
		$file = $bt[0]['file'];
		$line = $bt[0]['line'];
		$logmessage  = $file . ':' . $line . ' ' . $logmessage;
	}

    error_log( $logmessage ) ;

}