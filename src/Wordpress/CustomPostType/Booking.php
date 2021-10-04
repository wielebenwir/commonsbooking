<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Helper\Helper;
use WP_Post;

class Booking extends Timeframe {

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

		// Frontend request
		$this->handleFormRequest();

		add_action( 'save_post', array( $this, 'savePost' ), 1, 2 );

		// Set Tepmlates
		add_filter( 'the_content', array( $this, 'getTemplate' ) );

		// List settings
		$this->removeListDateColumn();

		// Backend listing columns.
		$this->listColumns = [
			'timeframe-author'                              => esc_html__( 'User', 'commonsbooking' ),
			'item-id'                                       => esc_html__( 'Item', 'commonsbooking' ),
			'location-id'                                   => esc_html__( 'Location', 'commonsbooking' ),
			'post_date'                                     => esc_html__( 'Bookingdate', 'commonsbooking' ),
			'repetition-start'                              => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END => esc_html__( 'End Date', 'commonsbooking' ),
			'post_status'                                   => esc_html__( 'Booking Status', 'commonsbooking' ),
		];

		// Add type filter to backend list view
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminStatusFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminDateFilter' ) );
		add_action( 'pre_get_posts', array( static::class, 'filterAdminList' ) );
	}

	/**
	 * Save the new Custom Fields values
	 * @throws \Exception
	 */
	public function savePost( $post_id, WP_Post $post ) {
		// This is just for bookings
		if ( $post->post_type !== static::getPostType() ) {
			return;
		}

		// Keep meta attributes after trashing
		if (
			array_key_exists( 'action', $_REQUEST ) &&
			( $_REQUEST['action'] == 'trash' || $_REQUEST['action'] == 'untrash' )
		) {
			return;
		}

		// Check if there is already an existing booking. If there is one, the current one will be
		// saved as draft.
		if (
			( array_key_exists( 'type', $_REQUEST ) && $_REQUEST['type'] == Timeframe::BOOKING_ID ) &&
			current_user_can( 'edit_' . self::$postType, $post_id )
		) {
			try {
				self::validateBookingParameters(
					sanitize_text_field( $_REQUEST["item-id"] ),
					sanitize_text_field( $_REQUEST["location-id"] ),
					sanitize_text_field( $_REQUEST["repetition-start"] ),
					sanitize_text_field( $_REQUEST["repetition-end"] )
				);
			} catch ( Exception $e ) {
				if ( $post->post_status !== 'draft' ) {
					$post->post_status = 'draft';
					wp_update_post( $post );
				}

				set_transient( \CommonsBooking\Model\Timeframe::ERROR_TYPE,
					commonsbooking_sanitizeHTML( __( "There is an overlapping booking.",
						'commonsbooking' ) ),
					45 );
			}
		}

		// Save custom fields
		$this->saveCustomFields( $post_id );

		// Validate timeframe
		$isValid = $this->validateTimeFrame( $post_id, $post );

		if ( $isValid ) {
			$timeframe = new \CommonsBooking\Model\Timeframe( $post_id );
		}
	}

	/**
	 * Handles frontend save-Request for timeframe.
	 * @throws \Exception
	 */
	public function handleFormRequest() {

		if (
			isset( $_REQUEST[ static::getWPNonceId() ] ) &&
			wp_verify_nonce( $_REQUEST[ static::getWPNonceId() ], static::getWPAction() )
		) {
			$itemId     = isset( $_REQUEST['item-id'] ) && $_REQUEST['item-id'] != "" ? sanitize_text_field( $_REQUEST['item-id'] ) : null;
			$locationId = isset( $_REQUEST['location-id'] ) && $_REQUEST['location-id'] != "" ? sanitize_text_field( $_REQUEST['location-id'] ) : null;
			$comment = isset( $_REQUEST['comment'] ) && $_REQUEST['comment'] != "" ? sanitize_text_field( $_REQUEST['comment'] ) : null;

			if ( ! get_post( $itemId ) ) {
				throw new Exception( 'Item does not exist. (' . $itemId . ')' );
			}
			if ( ! get_post( $locationId ) ) {
				throw new Exception( 'Location does not exist. (' . $locationId . ')' );
			}

			$startDate = null;
			if ( isset( $_REQUEST['repetition-start'] ) && $_REQUEST['repetition-start'] != "" ) {
				$startDate = sanitize_text_field( $_REQUEST['repetition-start'] );
			}

			$endDate = null;
			if (
				isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) &&
				$_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] != ""
			) {
				$endDate = sanitize_text_field( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] );
			}

			if ( $startDate == null || $endDate == null ) {
				throw new Exception( 'Start- and/or enddate missing.' );
			}

			/** @var \CommonsBooking\Model\Booking $booking */
			$booking = \CommonsBooking\Repository\Booking::getByDate(
				$startDate,
				$endDate,
				$locationId,
				$itemId
			);

			$postarr = array(
				"type"        => sanitize_text_field( $_REQUEST["type"] ),
				"post_status" => sanitize_text_field( $_REQUEST["post_status"] ),
				"post_type"   => self::getPostType(),
				"post_title"  => esc_html__( "Booking", 'commonsbooking' ),
				"meta_input" => [
					'comment'          => $comment
				]
			);

			// New booking
			if ( empty( $booking ) ) {
				$postarr['post_name']  = Helper::generateRandomString();
				$postarr['meta_input'] = [
					'location-id'      => $locationId,
					'item-id'          => $itemId,
					'repetition-start' => $startDate,
					'repetition-end'   => $endDate,
					'type'             => Timeframe::BOOKING_ID
				];
				$postId                = wp_insert_post( $postarr, true );
				// Existing booking
			} else {
				$postarr['ID'] = $booking->ID;
				$postId        = wp_update_post( $postarr );
			}

			$this->saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate );

			$bookingModel = new \CommonsBooking\Model\Booking( $postId );
			// we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
			$bookingModel->assignBookableTimeframeFields();

			// get slug as parameter
			$post_slug = get_post( $postId )->post_name;

			wp_redirect( add_query_arg( self::getPostType(), $post_slug, home_url() ) );
			exit;
		}
	}

	/**
	 * Multi grid size
	 * We need to save the grid size for timeframes with full slot grid.
	 *
	 * @param $postId
	 * @param $locationId
	 * @param $itemId
	 * @param $startDate
	 * @param $endDate
	 */
	private function saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate ): void {
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getRelevantTimeFrame( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getRelevantTimeFrame( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && $endTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE,
				$endTimeFrame->getGridSize()
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function getView() {
		return new \CommonsBooking\View\Booking();
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
			'supports'            => array( 'title', 'author', 'revisions' ),

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

	/**
	 * Returns true, if there are no already existing bookings.
	 *
	 * @param $itemId
	 * @param $locationId
	 * @param $startDate
	 * @param $endDate
	 *
	 * @throws Exception
	 * @throws \Exception
	 */
	protected static function validateBookingParameters( $itemId, $locationId, $startDate, $endDate ) {
		// Get exiting bookings for defined parameters
		$existingBookingsInRange = \CommonsBooking\Repository\Booking::getByTimerange(
			$startDate,
			$endDate,
			$locationId,
			$itemId
		);

		// If there are already bookings, throw exception
		if ( count( $existingBookingsInRange ) ) {
			throw new \Exception( __( 'There are already bookings in selected timerange.', 'commonsbooking' ) );
		}
	}
}