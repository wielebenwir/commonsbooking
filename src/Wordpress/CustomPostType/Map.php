<?php


namespace CommonsBooking\Wordpress\CustomPostType;


use CommonsBooking\Map\MapAdmin;
use CommonsBooking\Map\MapSettings;
use CommonsBooking\Map\MapShortcode;

use function __;

class Map extends CustomPostType {

	/**
	 * @var string
	 */
	public static $postType = 'cb_map';

	/**
	 * The default coordinates of the map center.
	 * Is used when no other coordinates are set.
	 * These are currently the coordinates of Cologne, Germany.
	 */
	const LATITUDE_DEFAULT = 50.937531;
	const LONGITUDE_DEFAULT = 6.960279;

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add shortcodes
		add_shortcode( 'cb_map', array( new MapShortcode(), 'execute' ) );

		// Add actions
		add_action( 'save_post_' . self::$postType, array( MapAdmin::class, 'validate_options' ), 10, 3 );
		add_action( 'add_meta_boxes_cb_map', array( MapAdmin::class, 'add_meta_boxes' ) );
	}

	public static function getView() {
		return new \CommonsBooking\View\Map();
	}

	public function getArgs() {
		$labels = array(
			'name'               => esc_html__( 'Maps', 'commonsbooking' ),
			'singular_name'      => esc_html__( 'Map', 'commonsbooking' ),
			'add_new'            => esc_html__( 'create CB map', 'commonsbooking' ),
			'add_new_item'       => esc_html__( 'create Commons Booking map', 'commonsbooking' ),
			'edit_item'          => esc_html__( 'edit Commons Booking map', 'commonsbooking' ),
			'new_item'           => esc_html__( 'create CB map', 'commonsbooking' ),
			'view_item'          => esc_html__( 'view CB map', 'commonsbooking' ),
			'search_items'       => esc_html__( 'search CB maps', 'commonsbooking' ),
			'not_found'          => esc_html__( 'no Commons Booking map found', 'commonsbooking' ),
			'not_found_in_trash' => esc_html__( 'no Commons Booking map found in the trash', 'commonsbooking' ),
			'parent_item_colon'  => esc_html__( 'parent CB maps', 'commonsbooking' ),
		);

		$supports = array(
			'title',
			'author',
		);

		return array(
			'labels'              => $labels,

			// Sichtbarkeit des Post Types
			'public'              => true,

			// Standard Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'             => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'        => false,

			// Position im Menu
			'menu_position'       => 5,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar'   => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus'   => true,
			'hierarchical'        => false,
			'description'         => esc_html__( 'Maps to show Commons Booking Locations and their Items', 'commonsbooking' ),
			'supports'            => $supports,
			'menu_icon'           => 'dashicons-location',
			'publicly_queryable'  => false,
			'exclude_from_search' => false,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => false,
			'delete_with_user'    => false,
			'capability_type'     => array( self::$postType, self::$postType . 's' ),
		);
	}

}
