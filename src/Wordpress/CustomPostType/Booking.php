<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Exception\OverlappingException;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Messages\BookingMessage;
use Exception;
use function wp_verify_nonce;
use function commonsbooking_write_log;

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
		// does not trigger when initiated in initHooks
		add_action( 'post_updated', array( $this, 'postUpdated' ), 1, 3 );

		// Frontend request
		$this->handleFormRequest();
	}

	/**
	 * Handles frontend save-Request for timeframe.
	 * @throws Exception
	 */
	public function handleFormRequest() {

		if (
			function_exists( 'wp_verify_nonce' ) &&
			isset( $_REQUEST[ static::getWPNonceId() ] ) &&
			wp_verify_nonce( $_REQUEST[ static::getWPNonceId() ], static::getWPAction() )
		) {
			$itemId     = isset( $_REQUEST['item-id'] ) && $_REQUEST['item-id'] != "" ? sanitize_text_field( $_REQUEST['item-id'] ) : null;
			$locationId = isset( $_REQUEST['location-id'] ) && $_REQUEST['location-id'] != "" ? sanitize_text_field( $_REQUEST['location-id'] ) : null;
			$comment    = isset( $_REQUEST['comment'] ) && $_REQUEST['comment'] != "" ? sanitize_text_field( $_REQUEST['comment'] ) : null;
            $post_status = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] != "" ? sanitize_text_field( $_REQUEST['post_status'] ) : null;

 			if ( ! get_post( $itemId ) ) {
				throw new Exception( 'Item does not exist. (' . $itemId . ')' );
			}
			if ( ! get_post( $locationId ) ) {
				throw new Exception( 'Location does not exist. (' . $locationId . ')' );
			}

			$startDate = null;
			if ( isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] ) &&
			     $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] != ""
			) {
				$startDate = sanitize_text_field( $_REQUEST[\CommonsBooking\Model\Timeframe::REPETITION_START] );
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

			// Validate booking -> check if there are no existing bookings in timerange.
			$existingBookings =
				\CommonsBooking\Repository\Booking::getByTimerange(
					$startDate,
					$endDate,
					$locationId,
					$itemId,
					[],
					['confirmed']
				)

			if(count($existingBookings) > 0 ) {
				$requestedPostname = array_key_exists('cb_booking', $_REQUEST) ? $_REQUEST['cb_booking'] : '';

				// checks if it's an edit, but ignores exact start/end time
				$isEdit = count($existingBookings) === 1 &&
					array_values($existingBookings)[0]->getPost()->post_name === $requestedPostname &&
					array_values($existingBookings)[0]->getPost()->post_author == get_current_user_id();

				if( (!$isEdit || count($existingBookings) > 1) && $post_status != 'canceled' ) {
					throw new Exception( 'There is already a booking in this timerange.' );
				}
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
				"meta_input"  => [
					'comment' => $comment
				]
			);

			// New booking
			if ( empty( $booking ) ) {
				$postarr['post_name']  = Helper::generateRandomString();
				$postarr['meta_input'] = [
					\CommonsBooking\Model\Timeframe::META_LOCATION_ID => $locationId,
					\CommonsBooking\Model\Timeframe::META_ITEM_ID     => $itemId,
					\CommonsBooking\Model\Timeframe::REPETITION_START => $startDate,
					\CommonsBooking\Model\Timeframe::REPETITION_END   => $endDate,
					'type'                                            => Timeframe::BOOKING_ID
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
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && !$startTimeFrame->isFullDay() && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && !$endTimeFrame->isFullDay() && $endTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE,
				$endTimeFrame->getGridSize()
			);
		}
	}

	/**
	 * Check if booking overlaps before its been saved.
	 *
	 * @param $postId
	 * @param $data
	 *
	 * @return void
	 */
	public static function preSavePost( $postId, $data ) {
		if ( static::$postType !== $data['post_type'] ) {
			return;
		}

		try {

			// Check if its an admin edit
			$requestKeys    = [
				\CommonsBooking\Model\Timeframe::META_ITEM_ID,
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID,
				\CommonsBooking\Model\Timeframe::REPETITION_START,
				\CommonsBooking\Model\Timeframe::REPETITION_END
			];
			$intersectCount = count( array_intersect( $requestKeys, array_keys( $_REQUEST ) ) );
			if ( $intersectCount < count( $requestKeys ) ) {
				return;
			}

			// prepare needed params
			$itemId          = sanitize_text_field( $_REQUEST[\CommonsBooking\Model\Timeframe::META_ITEM_ID] );
			$locationId      = sanitize_text_field( $_REQUEST[\CommonsBooking\Model\Timeframe::META_LOCATION_ID] );
			$repetitionStart = sanitize_text_field( $_REQUEST[\CommonsBooking\Model\Timeframe::REPETITION_START] );
			if ( is_array( $repetitionStart ) ) {
				$repetitionStart = strtotime( $repetitionStart['date'] . " " . $repetitionStart['time'] );
			} else {
				$repetitionStart = intval( $repetitionStart );
			}
			$repetitionEnd = sanitize_text_field( $_REQUEST[\CommonsBooking\Model\Timeframe::REPETITION_END] );
			if ( is_array( $repetitionEnd ) ) {
				$repetitionEnd = strtotime( $repetitionEnd['date'] . " " . $repetitionEnd['time'] );
			} else {
				$repetitionEnd = intval( $repetitionEnd );
			}

			self::validateBookingParameters(
				$itemId,
				$locationId,
				$repetitionStart,
				$repetitionEnd,
				$postId
			);
		} catch ( OverlappingException $e ) {
			set_transient( \CommonsBooking\Model\Timeframe::ERROR_TYPE,
				commonsbooking_sanitizeHTML( __( "There is an overlapping booking.",
					'commonsbooking' ) ),
				45 );
			$targetUrl = sanitize_url( $_REQUEST['_wp_http_referer'] );
			header( 'Location: ' . $targetUrl );
			exit();
		}
	}

	/**
	 * Returns true, if there are no already existing bookings.
	 *
	 * @param $itemId
	 * @param $locationId
	 * @param $startDate
	 * @param $endDate
	 * @param null $postId
	 *
	 * @throws OverlappingException
	 */
	protected static function validateBookingParameters( $itemId, $locationId, $startDate, $endDate, $postId = null ) {
		// Get exiting bookings for defined parameters
		$existingBookingsInRange = \CommonsBooking\Repository\Booking::getByTimerange(
			$startDate,
			$endDate,
			$locationId,
			$itemId
		);

		if ( $postId && count( $existingBookingsInRange ) == 1 ) {
			$booking = array_pop( $existingBookingsInRange );
			if ( $booking->ID == $postId ) {
				return;
			}
		}

		// If there are already bookings, throw exception
		if ( count( $existingBookingsInRange ) ) {
			throw new OverlappingException( __( 'There are already bookings in selected timerange.', 'commonsbooking' ) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function getView() {
		return new \CommonsBooking\View\Booking();
	}

	public function initListView() {
		if ( array_key_exists( 'post_type', $_GET ) && static::$postType !== $_GET['post_type'] ) {
			return;
		}

		// List settings
		$this->removeListDateColumn();

		// Backend listing columns.
		$this->listColumns = [
			'timeframe-author'                                => esc_html__( 'User', 'commonsbooking' ),
			'item-id'                                         => esc_html__( 'Item', 'commonsbooking' ),
			'location-id'                                     => esc_html__( 'Location', 'commonsbooking' ),
			'post_date'                                       => esc_html__( 'Bookingdate', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_START => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END   => esc_html__( 'End Date', 'commonsbooking' ),
			'post_status'                                     => esc_html__( 'Booking Status', 'commonsbooking' ),
			'comment'                                         => esc_html__( 'Comment', 'commonsbooking' ),
		];

		parent::initListView(); // TODO: Change the autogenerated stub
	}

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		add_action( 'pre_post_update', array( $this, 'preSavePost' ), 1, 2 );

		// Set Tepmlates
		add_filter( 'the_content', array( $this, 'getTemplate' ) );

		// Listing of bookings for current user
		add_shortcode( 'cb_bookings', array( \CommonsBooking\View\Booking::class, 'shortcode' ) );

		// Add type filter to backend list view
		//add_action( 'restrict_manage_posts', array( static::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminDateFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminStatusFilter' ) );
		add_action( 'pre_get_posts', array( static::class, 'filterAdminList' ) );

		// show permanent admin notice
		add_action( 'admin_notices', array( $this, 'BookingsAdminListNotice' ) );
	}

	/**
	 * loads template according and returns content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function getTemplate( $content ) {
		$cb_content = '';
		if ( is_singular( self::getPostType() ) && is_main_query() ){
			ob_start();
			global $post;

			if ( commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
				commonsbooking_get_template_part( 'booking', 'single' );
			} else {
				commonsbooking_get_template_part( 'booking', 'single-notallowed' );
			}
			$cb_content = ob_get_clean();
		} // if archive...

		return $content . $cb_content;
	}

	/**
	 * Is triggered when post gets updated. Currently used to send notifications regarding bookings.
	 *
	 * @param $post_ID
	 * @param $post_after
	 * @param $post_before
	 */
	public function postUpdated( $post_ID, $post_after, $post_before ) {
		if ( ! $this->hasRunBefore( __FUNCTION__ ) ) {
			$isBooking = get_post_meta( $post_ID, 'type', true ) == Timeframe::BOOKING_ID;
			if ( $isBooking ) {
				// Trigger Mail, only send mail if status has changed
				if ( $post_before->post_status != $post_after->post_status and
				     ! (
					     $post_before->post_status === 'unconfirmed' and
					     $post_after->post_status === 'canceled'
				     )
				) {
					if ( $post_after->post_status == 'canceled' ) {
						$booking = new \CommonsBooking\Model\Booking( $post_ID );
						$booking->cancel();
					} else {
						$booking_msg = new BookingMessage( $post_ID, $post_after->post_status );
						$booking_msg->triggerMail();
					}
				}
			}
		}
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

			'capabilities' => array(
				'create_posts' => false,
			),

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
	 * Adds data to custom columns
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function setCustomColumnsData( $column, $post_id ) {

		// we alter the  author column data and link the username to the user profile
		if ( $column == "timeframe-author" ) {
			$post           = get_post( $post_id );
			$timeframe_user = get_user_by( 'id', $post->post_author );
			echo '<a href="' . get_edit_user_link( $timeframe_user->ID ) . '">' . commonsbooking_sanitizeHTML( $timeframe_user->user_login ) . '</a>';
		}


		if ( $value = get_post_meta( $post_id, $column, true ) ) {
			switch ( $column ) {
				case 'location-id':
				case 'item-id':
					if ( $post = get_post( $value ) ) {
						if ( get_post_type( $post ) == Location::getPostType() ||
						     get_post_type( $post ) == Item::getPostType()
						) {
							echo commonsbooking_sanitizeHTML( $post->post_title );
							break;
						}
					}
					echo '-';
					break;
				case 'type':
					$output = "-";

					foreach ( $this->getCustomFields() as $customField ) {
						if ( $customField['id'] == 'type' ) {
							foreach ( $customField['options'] as $key => $label ) {
								if ( $value == $key ) {
									$output = $label;
								}
							}
						}
					}
					echo commonsbooking_sanitizeHTML( $output );
					break;
				case \CommonsBooking\Model\Timeframe::REPETITION_START:
				case \CommonsBooking\Model\Timeframe::REPETITION_END:
					echo date( 'd.m.Y H:i', $value );
					break;
				default:
					echo commonsbooking_sanitizeHTML( $value );
					break;
			}
		} else {
			$bookingColumns = [
				'post_date',
				'post_status',
			];

			if (
				property_exists( $post = get_post( $post_id ), $column ) && (
					! in_array( $column, $bookingColumns ) ||
					get_post_meta( $post_id, 'type', true ) == Timeframe::BOOKING_ID
				)
			) {
				echo commonsbooking_sanitizeHTML( $post->{$column} );
			}
		}
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
				'name' => esc_html__( 'Edit booking', 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML( __( 'In this view, only the start and end dates of the booking can be adjusted by administrators.<br>
				Click on the <strong>preview button on the right panel</strong> to view more booking details and to cancel the booking via the cancel button.<br>
				Please note that when changing the booking period, there is no check for possible vacations or non-bookable periods. There is only a check if the booking overlaps with an already existing other booking. In this case, saving is not possible. 
				Further booking data cannot be changed subsequently. <br>We recommend in this case to cancel the booking and make a new booking.', 'commonsbooking' ) ),
				'id'   => "title-booking-hint",
				'type' => 'title',
			),
			array(
				'name'       => esc_html__( "Comment", 'commonsbooking' ),
				'desc'       => esc_html__( 'This comment is internal for timeframes like bookable, repair, holiday. If timeframe is a booking this comment can be set by users during the booking confirmation process.', 'commonsbooking' ),
				'id'         => "comment",
				'attributes' => array(
					'readonly' => 'readonly'
				),
				'type'       => 'textarea_small',
			),
			array(
				'name'       => esc_html__( "Location", 'commonsbooking' ),
				'id'         => "location-id",
				'type'       => 'select',
				'attributes' => array(
					'readonly' => 'readonly'
				),
				'options'    => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'       => esc_html__( "Item", 'commonsbooking' ),
				'id'         => "item-id",
				'type'       => 'select',
				'attributes' => array(
					'readonly' => 'readonly'
				),
				'options'    => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the start date. If you have selected repetition, this is the start date of the interval. ', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_START,
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				)
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the end date. If you have selected repetition, this is the end date of the interval. Leave blank if you do not want to set an end date.', 'commonsbooking' ),
				'id'          => "repetition-end",
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				)
			),
			array(
				'name'       => esc_html__( 'Booking Code', 'commonsbooking' ),
				'id'         => COMMONSBOOKING_METABOX_PREFIX . 'bookingcode',
				'type'       => 'text',
				'attributes' => array(
					'readonly' => 'readonly'
				)
			),
			array(
				'type'    => 'hidden',
				'id'      => 'prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) )
			),
		);
	}

	/**
	 * Display permanent Admin notice on admin edit listing page for post type booking
	 *
	 * @return void
	 */
	public function BookingsAdminListNotice() {
		global $pagenow;

		$notice = commonsbooking_sanitizeHTML( __( 'Bookings should not be created, edited or canceled via this admin list. 
		Click on preview to show booking details in frontend<br>
		To search and filter bookings please integrate the frontend booking list via shortcode. 
		See here <a target="_blank" href="https://commonsbooking.org/?p=1433">How to display the booking list</a>', 'commonsbooking' ) );

		if ( ( $pagenow == 'edit.php' ) && isset( $_GET['post_type'] ) ) {
			if ( sanitize_text_field( $_GET['post_type'] ) == self::getPostType() ) {
				echo '<div class="notice notice-info"><p>' . commonsbooking_sanitizeHTML( $notice ) . '</p></div>';
			}
		}
	}
}
