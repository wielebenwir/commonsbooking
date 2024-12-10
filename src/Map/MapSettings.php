<?php

namespace CommonsBooking\Map;

class MapSettings {

	const BOOKING_PAGE_LINK_REPLACEMENT_DEFAULT = true;

	const OPTION_KEYS = array( 'booking_page_link_replacement' );

	public static $options;

	/**
	 * option getter
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function get_option( $key ) {
		self::load_options();

		return commonsbooking_sanitizeHTML( self::$options[ $key ] );
	}

	/**
	 * load CB Map settings options
	 **/
	private static function load_options() {
		if ( ! isset( self::$options ) ) {
			$options       = get_option( 'cb_map_options', array() );
			self::$options = self::populate_option_defaults( $options );
		}
	}

	/**
	 * populate the default values to the options
	 **/
	public static function populate_option_defaults( $options ) {
		foreach ( self::OPTION_KEYS as $key ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = self::get_option_default( $key );
			}
		}

		return $options;
	}

	/**
	 * get the default value for the option with given name
	 **/
	private static function get_option_default( $option_name ) {

		$default_name = strtoupper( $option_name ) . '_DEFAULT';

		$const_value = constant( "self::$default_name" );

		return $const_value ?? null;
	}

	/**
	 * prepare the plugin's settings and add settings page
	 **/
	public function prepare_settings() {

		add_action(
			'admin_menu',
			function () {
				add_options_page(
					esc_html__( 'Settings for Commons Booking Map', 'commonsbooking' ),
					esc_html__( 'Commons Booking Map', 'commonsbooking' ),
					'manage_options',
					'commonsbooking',
					array( $this, 'render_settings_page' )
				);
			}
		);

		add_action(
			'admin_init',
			function () {
				register_setting( 'cb-map-settings', 'cb_map_options', array( $this, 'validate_options' ) );
			}
		);

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * sanitize and validate the options provided by input array
	 **/
	public function validate_options( $input = array() ) {
		self::load_options();

		$validated_input = self::populate_option_defaults( array() );

		$validated_input['booking_page_link_replacement'] = isset( $input['booking_page_link_replacement'] );

		return $validated_input;
	}

	/**
	 * add the the link to settings page
	 **/
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=commons-booking-map">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * render the settings page
	 **/
	public function render_settings_page() {
		wp_enqueue_style( 'cb_map_admin_css', COMMONSBOOKING_MAP_ASSETS_URL . 'css/cb-map-admin.css' );

		include_once COMMONSBOOKING_MAP_PATH . 'templates/map-settings-page-template.php';
	}
}
