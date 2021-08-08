<?php


namespace CommonsBooking\Wordpress\CustomPostType;


use CommonsBooking\Repository\UserRepository;

class Restriction extends CustomPostType {

	/**
	 * @var string
	 */
	public static $postType = 'cb_restriction';

	private const SEND_BUTTON_ID = 'restriction-send';

	public static $types = [
		'repair' => 'Totalausfall',
		'hint'   => 'Hinweis'
	];

	/**
	 * Restriction constructor.
	 */
	public function __construct() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		// Remove not needed Meta Boxes
		add_action( 'do_meta_boxes', array( $this, 'removeDefaultCustomFields' ), 10, 3 );

		add_action( 'save_post_' . self::$postType, array( $this, 'handleFormRequest' ), 10, 3 );
	}

	/**
	 * @return string[]
	 */
	public static function getTypes(): array {
		return self::$types;
	}

	/**
	 * @inheritDoc
	 */
	public static function getView() {
		return new \CommonsBooking\View\Restriction();
	}

	/**
	 * @inheritDoc
	 */
	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Restrictions', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Restriction', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new Restriction', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit Restriction', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new Restriction', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show Restriction', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show Restrictions', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search Restrictions', 'commonsbooking' ),
			'not_found'             => esc_html__( 'Restrictions not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No Restrictions found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent Restrictions:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All Restrictions', 'commonsbooking' ),
			'archives'              => esc_html__( 'Restriction archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Restriction attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to Restriction', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to Restriction', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Restriction image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set Restriction image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove Restriction image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as Restriction image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Restrictions', 'commonsbooking' ),
		);

		// args for the new post_type
		return array(
			'labels'            => $labels,

			// Sichtbarkeit des Post Types
			'public'            => true,

			// Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'           => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'      => false,

			// Position im Menu
			'menu_position'     => 8,

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
				'title'        => esc_html__( 'Restriction', 'commonsbooking' ),
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
				'name'    => esc_html__( 'Type', 'commonsbooking' ),
				'desc'    => esc_html__( 'Select Type of this timeframe (e.g. bookable, repair, holidays, booking). See Documentation for detailed information.', 'commonsbooking' ),
				'id'      => \CommonsBooking\Model\Restriction::META_TYPE,
				'type'    => 'select',
				'options' => self::getTypes(),
			),
			array(
				'name'             => esc_html__( "Location", 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Restriction::META_LOCATION_ID,
				'type'             => 'select',
				'show_option_none' => esc_html__( 'None', 'commonsbooking' ),
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'             => esc_html__( "Item", 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Restriction::META_ITEM_ID,
				'type'             => 'select',
				'show_option_none' => esc_html__( 'None', 'commonsbooking' ),
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name' => esc_html__( "Hint", 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Restriction::META_HINT,
				'type' => 'textarea'
			),
			array(
				'name' => esc_html__( "Active", 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Restriction::META_ACTIVE,
				'type' => 'checkbox',
			),
			array(
				'name' => esc_html__( 'Start date', 'commonsbooking' ),
				'desc' => esc_html__( 'Set the start date. If you have selected repetition, this is the start date of the interval. ', 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Restriction::META_START,
				'type' => 'text_datetime_timestamp'
			),
			array(
				'name' => esc_html__( 'End date', 'commonsbooking' ),
				'desc' => esc_html__( 'Set the end date. If you have selected repetition, this is the end date of the interval. Leave blank if you do not want to set an end date.', 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Restriction::META_END,
				'type' => 'text_datetime_timestamp'
			),
			array(
				'type'    => 'hidden',
				'id'      => 'restriction-prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) )
			),
			array(
				'name'          => esc_html__( 'Send Restriction', 'commonsbooking' ),
//				'desc' => esc_html__( '....', 'commonsbooking' ),
				'id'            => self::SEND_BUTTON_ID,
				'type'          => 'text',
				'render_row_cb' => array( \CommonsBooking\View\Restriction::class, 'renderSendButton' ),
			),
			array(
				'id'   => \CommonsBooking\Model\Restriction::META_SENT,
				'type' => 'hidden',
			)
		);
	}

	/**
	 * Handles save-Request for location.
	 */
	public function handleFormRequest( $post_id, $post, $update ) {
		if ( $this->hasRunBefore( __METHOD__ ) ) {
			return;
		}

		$postType = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : null;

		if ( $postType == self::$postType && $post_id ) {
			if ( array_key_exists( self::SEND_BUTTON_ID, $_REQUEST ) ) {
				update_post_meta( $post_id, \CommonsBooking\Model\Restriction::META_SENT, time() );
				$restriction = new \CommonsBooking\Model\Restriction( $post_id );
				$restriction->apply();
			}
		}
	}

}