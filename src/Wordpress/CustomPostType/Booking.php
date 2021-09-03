<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Repository\UserRepository;

class Booking extends CustomPostType {

	/**
	 * @var string
	 */
	public static $postType = 'cb_booking';

	/**
	 * Position in backend menu.
	 * @var int
	 */
	protected $menuPosition = 4;

	public function __construct() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );
	}

	/**
	 * @inheritDoc
	 */
	public static function getView() {
		// TODO: Implement getView() method.
	}

	/**
	 * Returns CPT arguments.
	 * @return array
	 */
	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Bookings', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Booking', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new booking', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit booking', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new booking', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show booking', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show bookings', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search bookings', 'commonsbooking' ),
			'not_found'             => esc_html__( 'Timeframes not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No bookings found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent bookings:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All bookings', 'commonsbooking' ),
			'archives'              => esc_html__( 'Timeframe archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Timeframe attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to booking', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to booking', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Timeframe image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set booking image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove booking image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as booking image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Timeframes', 'commonsbooking' ),
		);

		// args for the new post_type
		return array(
			'labels'            => $labels,

			// Sichtbarkeit des Post Types
			'public'            => false,

			// Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'           => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'      => false,

			// Position im Menu
			'menu_position'     => 2,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar' => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus' => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die standart Werte post und page in form eines Strings gesetzt werden
			'capability_type'   => array( self::$postType, self::$postType . 's' ),

			'map_meta_cap'        => true,

			// Soll es im Frontend abrufbar sein?
			'publicly_queryable'  => true,

			// Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
			'exclude_from_search' => true,

			// Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
			'supports'            => array( 'title', 'author', 'custom-fields', 'revisions' ),

			// Soll der Post Type Archiv-Seiten haben?
			'has_archive'         => false,

			// Soll man den Post Type exportieren können?
			'can_export'          => false,

			// Slug unseres Post Types für die redirects
			// dieser Wert wird später in der URL stehen
			'rewrite'             => array( 'slug' => self::getPostType() ),

			'show_in_rest' => true
		);
	}

	/**
	 * Registers metaboxes for cpt.
	 */
	public function registerMetabox() {
		$cmb = new_cmb2_box(
			[
				'id'           => static::getPostType() . "-custom-fields",
				'title'        => esc_html__( 'Booking', 'commonsbooking' ),
				'object_types' => array( static::getPostType() ),
			]
		);


		foreach ( $this->getCustomFields() as $customField ) {
			$cmb->add_field( $customField );
		}
	}

	/**
	 * Returns custom (meta) fields for Costum Post Type Timeframe.
	 * @return array
	 */
	protected function getCustomFields() {
		// We need static types, because german month names dont't work for datepicker
		$dateFormat = "d/m/Y";
		if ( strpos( get_locale(), 'de_' ) !== false ) {
			$dateFormat = "d.m.Y";
		}

		if ( strpos( get_locale(), 'en_' ) !== false ) {
			$dateFormat = "m/d/Y";
		}

		return array(
			array(
				'name' => esc_html__( "Comment", 'commonsbooking' ),
				'desc' => esc_html__( 'This comment is internal for timeframes like bookable, repair, holiday. If timeframe is a booking this comment can be set by users during the booking confirmation process.', 'commonsbooking' ),
				'id'   => "comment",
				'type' => 'textarea_small',
			),
			array(
				'name'    => esc_html__( "Location", 'commonsbooking' ),
				'id'      => "location-id",
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'    => esc_html__( "Item", 'commonsbooking' ),
				'id'      => "item-id",
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name' => esc_html__( 'Full day', 'commonsbooking' ),
				'desc' => esc_html__(
					'If this option is selected, users can choose only whole days for pickup and return. No specific time slots for pickup or return are offered. Select this option if the pickup/return should be arranged personally between the location and the user. '
					, 'commonsbooking' ),
				'id'   => "full-day",
				'type' => 'checkbox',
			),
			array(
				'name'        => esc_html__( "Start time", 'commonsbooking' ),
				'id'          => "start-time",
				'type'        => 'text_time',
				'show_on_cb'  => 'cmb2_hide_if_no_cats', // function should return a bool value
				'attributes'  => array(
					'data-timepicker' => json_encode(
						array(
							'stepMinute' => 60,
						)
					),
				),
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
			),
			array(
				'name'        => esc_html__( "End time", 'commonsbooking' ),
				'id'          => "end-time",
				'type'        => 'text_time',
				'attributes'  => array(
					'data-timepicker' => json_encode(
						array(
							'stepMinute' => 60,
						)
					),
				),
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
			),
			array(
				'name'    => esc_html__( "Grid", 'commonsbooking' ),
				'desc'    => esc_html__( 'Choose whether users can only select the entire from/to time period when booking (full slot) or book within the time period in an hourly grid. See the documentation: <a target="_blank" href="https://commonsbooking.org/?p=437">Manage Booking Timeframes</a>', 'commonsbooking' ),
				'id'      => "grid",
				'type'    => 'select',
				'options' => Timeframe::getGridOptions(),
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the start date. If you have selected repetition, this is the start date of the interval. ', 'commonsbooking' ),
				'id'          => "repetition-start",
				'type'        => 'text_date_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
			),
			array(
				'name'    => esc_html__( 'Weekdays', 'commonsbooking' ),
				'id'      => "weekdays",
				'type'    => 'multicheck',
				'options' => [
					1 => esc_html__( "Monday", 'commonsbooking' ),
					2 => esc_html__( "Tuesday", 'commonsbooking' ),
					3 => esc_html__( "Wednesday", 'commonsbooking' ),
					4 => esc_html__( "Thursday", 'commonsbooking' ),
					5 => esc_html__( "Friday", 'commonsbooking' ),
					6 => esc_html__( "Saturday", 'commonsbooking' ),
					7 => esc_html__( "Sunday", 'commonsbooking' ),
				],
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the end date. If you have selected repetition, this is the end date of the interval. Leave blank if you do not want to set an end date.', 'commonsbooking' ),
				'id'          => "repetition-end",
				'type'        => 'text_date_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
			),
			array(
				'name'       => esc_html__( 'Booking Code', 'commonsbooking' ),
				'id'         => COMMONSBOOKING_METABOX_PREFIX . 'bookingcode',
				'type'       => 'text',
				'show_on_cb' => array( self::class, 'isOfTypeBooking' ),
				'attributes' => array(
					'disabled' => 'disabled',
				),
			),
			array(
				'type'    => 'hidden',
				'id'      => 'prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) )
			),
		);
	}
}