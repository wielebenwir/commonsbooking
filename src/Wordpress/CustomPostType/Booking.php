<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Messages\BookingMessage;
use CommonsBooking\Service\BookingRuleApplied;
use CommonsBooking\Service\iCalendar;
use Exception;
use function wp_verify_nonce;

/**
 * Booking
 */
class Booking extends Timeframe {

	// this is the error type for the validation that failed for the FRONTEND user
	// TODO: Switch the error type with the one from Model/Booking, because most functions regarding backend booking are in this class
	public const ERROR_TYPE        = COMMONSBOOKING_PLUGIN_SLUG . '-bookingValidationError';
	private const SUBMIT_BUTTON_ID = 'booking-submit';

	/**
	 * @var string
	 */
	public static $postType = 'cb_booking';

	/**
	 * Position in backend menu.
	 *
	 * @var int
	 */
	protected $menuPosition = 4;

	public function __construct() {

		// does not trigger when initiated in initHooks
		add_action( 'post_updated', array( $this, 'postUpdated' ), 1, 3 );
	}


	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		// we need to add some additional fields and modify the autor if admin booking is made
		add_action( 'save_post_' . self::$postType, array( $this, 'savePost' ), 10 );

		// Set Tepmlates
		add_filter( 'the_content', array( $this, 'getTemplate' ) );

		// Listing of bookings for current user
		add_shortcode( 'cb_bookings', array( \CommonsBooking\View\Booking::class, 'shortcode' ) );

		// Add type filter to backend list view
		// add_action( 'restrict_manage_posts', array( static::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminDateFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminStatusFilter' ) );
		add_action( 'pre_get_posts', array( static::class, 'filterAdminList' ) );

		// show admin notice
		add_action( 'admin_notices', array( $this, 'displayBookingsAdminListNotice' ) );
		add_action( 'edit_form_top', array( $this, 'displayOverlappingBookingNotice' ), 99 );
	}

	/**
	 * Adds and modifies some booking CPT fields in order to make admin boookings
	 * compatible to user made bookings via frontend.
	 *
	 * @param  mixed $post_id
	 * @param  mixed $post
	 * @param  mixed $update
	 * @return void
	 */
	public function savePost( $post_id, $post = null, $update = null ) {
		global $pagenow;

		$post            = $post ?? get_post( $post_id );
		$is_trash_action = str_contains( $_REQUEST['action'] ?? '', 'trash' );

		// we check if it's a new created post - TODO: This is not the case
		if (
			! empty( $_REQUEST ) &&
			! $is_trash_action &&
			$pagenow === 'post.php' &&
			( commonsbooking_isCurrentUserAdmin() || commonsbooking_isCurrentUserCBManager() )
		) {
			// set request variables
			$booking_user = isset( $_REQUEST['booking_user'] ) ? esc_html( $_REQUEST['booking_user'] ) : false;

			$post_status = esc_html( $_REQUEST['post_status'] ?? '' );

			$start_time = isset( $_REQUEST['repetition-start'] ) ? esc_html( $_REQUEST['repetition-start']['time'] ?? '' ) : false;
			$end_time   = isset( $_REQUEST['repetition-end'] ) ? esc_html( $_REQUEST['repetition-end']['time'] ?? '' ) : false;
			$full_day   = ( ! $start_time || $start_time === '0:00' || $start_time === '00:00' ) && ( ! $end_time || $end_time === '23:59' ) ? 'on' : '';

			$postarr = array(
				'post_title'  => esc_html__( 'Admin-Booking', 'commonsbooking' ),
				'post_author' => (int) $booking_user,
				'post_status' => $post_status,
				'meta_input'  => [
					'admin_booking_id' => get_current_user_id(),
					'start-time'       => $start_time,
					'end-time'         => $end_time,
					'type'             => Timeframe::BOOKING_ID,
					'grid'             => '',
					'full-day'         => $full_day,
				],
			);

			// set post_name if new post
			if ( in_array( $post->post_status, array( 'auto-draft', 'new' ) ) || $post->post_name === '' ) {
				$postarr['post_name'] = Helper::generateRandomString();
			}

			$postarr['ID'] = $post_id;

			// unhook this function so it doesn't loop infinitely
			remove_action( 'save_post_' . self::$postType, array( $this, 'savePost' ) );

			// update this post
			wp_update_post( $postarr, true, true );

			// run validation only on new posts (the submit button is only available on new posts)
			if ( array_key_exists( self::SUBMIT_BUTTON_ID, $_REQUEST ) ) {
				try {
					$booking = new \CommonsBooking\Model\Booking( $post_id );
					$booking->isValid();
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_status' => 'confirmed',
						)
					);
					$post_status = 'confirmed';
				} catch ( TimeframeInvalidException $e ) {
					// set to draft and display error message
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_status' => 'draft',
						)
					);
					set_transient(
						\CommonsBooking\Model\Booking::ERROR_TYPE,
						nl2br( commonsbooking_sanitizeHTML( $e->getMessage() ) ),
						30 // Expires very quickly, so that outdated messsages will not be shown to the user
					);
				}
			}

			// readd the hook
			add_action( 'save_post_' . self::$postType, array( $this, 'savePost' ) );

			// if we just created a new confirmed booking we trigger the confirmation mail
			if ( $post_status == 'confirmed' ) {
				$booking_msg = new BookingMessage( $post_id, $post_status );
				$booking_msg->triggerMail();
			}
		}
	}

	/**
	 * Handles frontend save-Request for timeframe.
	 *
	 * @throws BookingDeniedException - if booking is not allowed, contains translated error message for the user
	 */
	public static function handleFormRequest() {
		if (
			function_exists( 'wp_verify_nonce' ) &&
			isset( $_REQUEST[ static::getWPNonceId() ] ) &&
			wp_verify_nonce( $_REQUEST[ static::getWPNonceId() ], static::getWPAction() ) // phpcs:ignore
		) {
			$itemId          = isset( $_REQUEST['item-id'] ) && $_REQUEST['item-id'] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST['item-id'] ) ) : null;
			$locationId      = isset( $_REQUEST['location-id'] ) && $_REQUEST['location-id'] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST['location-id'] ) ) : null;
			$comment         = isset( $_REQUEST['comment'] ) && $_REQUEST['comment'] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST['comment'] ) ) : null;
			$post_status     = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) ) : null;
			$post_ID         = isset( $_REQUEST['post_ID'] ) && $_REQUEST['post_ID'] !== '' ? intval( $_REQUEST['post_ID'] ) : null;
			$overbookedDays  = isset( $_REQUEST['days-overbooked'] ) && $_REQUEST['days-overbooked'] !== '' ? intval( $_REQUEST['days-overbooked'] ) : 0;
			$repetitionStart = isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] ) && $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] ) ) : null;
			$repetitionEnd   = isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) && $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) ) : null;
			$postName        = isset( $_REQUEST['cb_booking'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cb_booking'] ) ) : null;
			$postType        = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : null;

			$postId = self::handleBookingRequest(
				$itemId,
				$locationId,
				$post_status,
				$post_ID,
				$comment,
				$repetitionStart,
				$repetitionEnd,
				$postName,
				$postType,
				$overbookedDays
			);

			// get slug as parameter
			$post_slug = get_post( $postId )->post_name;

			wp_safe_redirect( add_query_arg( self::getPostType(), $post_slug, home_url() ) );
			exit;
		}
	}


	/**
	 *
	 * Will handle the frontend booking request. We moved this to a separate function
	 * so that we can test it.
	 *
	 * @param string|null $itemId
	 * @param string|null $locationId
	 * @param string|null $post_status
	 * @param int|null    $post_ID
	 * @param string|null $comment
	 * @param string|null $repetitionStart
	 * @param string|null $repetitionEnd
	 * @param string|null $requestedPostName
	 * @param string|null $postType
	 *
	 * @return int - the post id of the created booking
	 * @throws BookingDeniedException - if the booking is not possible, message contains translated text for the user
	 */
	public static function handleBookingRequest(
		?string $itemId,
		?string $locationId,
		?string $post_status,
		?int $post_ID,
		?string $comment,
		?string $repetitionStart,
		?string $repetitionEnd,
		?string $requestedPostName,
		?string $postType,
		int $overbookedDays = 0
	): int {

		if ( isset( $_POST['calendar-download'] ) ) {
			try {
				iCalendar::downloadICS( $post_ID );
			} catch ( Exception $e ) {
				// redirect to booking page and do nothing
				return $post_ID;
			}
			exit;
		}

		if ( $itemId === null || ! filter_var( $itemId, FILTER_VALIDATE_INT ) || ! get_post( (int) $itemId ) ) {
			// translators: $s = id of the item
			throw new BookingDeniedException( sprintf( __( 'Item does not exist. (%s)', 'commonsbooking' ), $itemId ) );
		}

		if ( $locationId === null || ! filter_var( $locationId, FILTER_VALIDATE_INT ) || ! get_post( (int) $locationId ) ) {
			// translators: $s = id of the location
			throw new BookingDeniedException( sprintf( __( 'Location does not exist. (%s)', 'commonsbooking' ), $locationId ) );
		}

		if ( $repetitionStart === null || $repetitionEnd === null ) {
			throw new BookingDeniedException( __( 'Start- and/or end-date is missing.', 'commonsbooking' ) );
		}

		// Validation end, set correctly typed params
		$itemId          = (int) $itemId;
		$locationId      = (int) $locationId;
		$repetitionStart = (int) $repetitionStart;
		$repetitionEnd   = (int) $repetitionEnd;

		if ( $post_ID != null && ! get_post( $post_ID ) ) {
			throw new BookingDeniedException(
				__( 'Your reservation has expired, please try to book again', 'commonsbooking' ),
				add_query_arg( 'cb-location', $locationId, get_permalink( get_post( $itemId ) ) )
			);
		}

		/** @var \CommonsBooking\Model\Booking|null $booking */
		$booking = \CommonsBooking\Repository\Booking::getByDate(
			$repetitionStart,
			$repetitionEnd,
			$locationId,
			$itemId
		);

		$existingBookings =
			\CommonsBooking\Repository\Booking::getExistingBookings(
				$itemId,
				$locationId,
				$repetitionStart,
				$repetitionEnd,
				$booking->ID ?? null,
			);

		// delete unconfirmed booking if booking process is canceled by user
		if ( $post_status === 'delete_unconfirmed' && $booking->ID === $post_ID ) {
			wp_delete_post( $post_ID );
			throw new BookingDeniedException(
				__( 'Booking canceled.', 'commonsbooking' ),
				add_query_arg( 'cb-location', $locationId, get_permalink( get_post( $itemId ) ) )
			);
		}

		// Validate booking -> check if there are no existing bookings in timerange.
		if ( count( $existingBookings ) > 0 ) {
			// checks if it's an edit, but ignores exact start/end time
			$isEdit = count( $existingBookings ) === 1 &&
						array_values( $existingBookings )[0]->getPost()->post_name === $requestedPostName &&
						intval( array_values( $existingBookings )[0]->getPost()->post_author ) === get_current_user_id();

			if ( ( ! $isEdit || count( $existingBookings ) > 1 ) && $post_status !== 'canceled' ) {
				if ( $booking ) {
					$post_status = 'unconfirmed';
				} else {
					throw new BookingDeniedException( __( 'There is already a booking in this time-range. This notice may also appear if there is an unconfirmed booking in the requested period. Unconfirmed bookings are deleted after about 10 minutes. Please try again in a few minutes.', 'commonsbooking' ) );
				}
			}
		}

		// add internal comment if admin edited booking via frontend TODO: This does not happen anymore, no admin bookings are made through the frontend
		if ( $booking && $booking->post_author !== '' && intval( $booking->post_author ) !== intval( get_current_user_id() ) ) {
			$postarr['meta_input']['admin_booking_id'] = get_current_user_id();
			$internal_comment                          = esc_html__( 'status changed by admin user via frontend. New status: ', 'commonsbooking' ) . $post_status;
			$booking->appendToInternalComment( $internal_comment, get_current_user_id() );
		}

		$postarr['type']                  = $postType;
		$postarr['post_status']           = $post_status;
		$postarr['post_type']             = self::getPostType();
		$postarr['post_title']            = esc_html__( 'Booking', 'commonsbooking' );
		$postarr['meta_input']['comment'] = $comment;

		// New booking
		if ( empty( $booking ) ) {
			$postarr['post_name']  = Helper::generateRandomString();
			$postarr['meta_input'] = array(
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID   => $locationId,
				\CommonsBooking\Model\Timeframe::META_ITEM_ID       => $itemId,
				\CommonsBooking\Model\Timeframe::REPETITION_START   => $repetitionStart,
				\CommonsBooking\Model\Timeframe::REPETITION_END     => $repetitionEnd,
				'type'                                              => Timeframe::BOOKING_ID,
			);

			$postId          = wp_insert_post( $postarr, true );
			$needsValidation = true;

			// Existing booking
		} else {
			$postarr['ID'] = $booking->ID;
			if ( $postarr['post_status'] === 'canceled' ) {
				$postarr['meta_input']['cancellation_time'] = current_time( 'timestamp' );
			}
			$postId = wp_update_post( $postarr );

			// we check if this is an already denied booking and demand validation again
			if ( $postarr['post_status'] == 'unconfirmed' ) {
				$needsValidation = true;
			} else {
				$needsValidation = false;
			}
		}

		self::saveGridSizes( $postId, $locationId, $itemId, $repetitionStart, $repetitionEnd );

		$bookingModel = new \CommonsBooking\Model\Booking( $postId );
		// we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
		try {
			$bookingModel->assignBookableTimeframeFields();
			if ( $overbookedDays > 0 ) { // avoid setting the value when not present (for example when updating the booking)
				$bookingModel->setOverbookedDays( $overbookedDays );
			}
		} catch ( \Exception $e ) {
			throw new BookingDeniedException(
				__( 'There was an error while saving the booking. Please try again. Thrown error:', 'commonsbooking' ) .
												PHP_EOL . $e->getMessage()
			);
		}

		// check if the Booking we want to create conforms to the set booking rules
		if ( $needsValidation ) {
			try {
				BookingRuleApplied::bookingConformsToRules( $bookingModel );
			} catch ( BookingDeniedException $e ) {
				wp_delete_post( $bookingModel->ID );
				throw new BookingDeniedException( $e->getMessage() );
			}
		}

		if ( $postId instanceof \WP_Error ) {
			throw new BookingDeniedException(
				__( 'There was an error while saving the booking. Please try again. Resulting WP_ERROR: ', 'commonsbooking' ) .
												PHP_EOL . implode( ', ', $postId->get_error_messages() )
			);
		}

		return $postId;
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
	private static function saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate ): void {
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && ! $startTimeFrame->isFullDay() && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && ! $endTimeFrame->isFullDay() && $endTimeFrame->getGrid() == 0 ) {
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

	public function initListView() {
		if ( array_key_exists( 'post_type', $_GET ) && static::$postType !== $_GET['post_type'] ) {
			return;
		}

		// List settings
		$this->removeListDateColumn();

		// Backend listing columns.
		$this->listColumns = [
			'booking_user'   => esc_html__( 'User', 'commonsbooking' ),
			'item-id'          => esc_html__( 'Item', 'commonsbooking' ),
			'location-id'      => esc_html__( 'Location', 'commonsbooking' ),
			'post_date'        => esc_html__( 'Bookingdate', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_START => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END => esc_html__( 'End Date', 'commonsbooking' ),
			'post_status'      => esc_html__( 'Booking Status', 'commonsbooking' ),
			'comment'          => esc_html__( 'Comment', 'commonsbooking' ),
		];

		parent::initListView(); // TODO: Change the autogenerated stub
	}

	/**
	 * Loads template according to global set post and to whether the user is authorized and returns content.
	 *
	 * @param string $content value of content parameter of `the_content` filter
	 *
	 * @return string
	 */
	public function getTemplate( $content ) {
		$cb_content = '';
		if ( ! post_password_required() &&
			is_singular( self::getPostType() ) && is_main_query() ) {
			ob_start();
			global $post;

			if ( commonsbooking_isCurrentUserAllowedToSee( $post ) ) {
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
	 *
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
			'not_found'             => esc_html__( 'Bookings not found', 'commonsbooking' ),
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
			'labels'              => $labels,

			// Sichtbarkeit des Post Types
			'public'              => false,

			// Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'             => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'        => false,

			// Position im Menu
			'menu_position'       => 2,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar'   => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus'   => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die standart Werte post und page in form eines Strings gesetzt werden
			'capability_type'     => array( self::$postType, self::$postType . 's' ),

			'map_meta_cap'        => true,

			// Soll es im Frontend abrufbar sein?
			'publicly_queryable'  => true,

			// Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
			'exclude_from_search' => true,

			// Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
			'supports'            => array( 'title', 'revisions' ),

			// Soll der Post Type Archiv-Seiten haben?
			'has_archive'         => false,

			// Soll man den Post Type exportieren können?
			'can_export'          => false,

			// Slug unseres Post Types für die redirects
			// dieser Wert wird später in der URL stehen
			'rewrite'             => array( 'slug' => self::getPostType() ),

			'show_in_rest'        => true,
		);
	}

	/**
	 * Adds data to custom columns
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function setCustomColumnsData( $column, $post_id ) {
		global $pagenow;

		if ( $pagenow !== 'edit.php' || empty( esc_html( $_GET['post_type'] ) ) || esc_html( $_GET['post_type'] ) !== $this::$postType ) {
			return;
		}

		// we alter the  author column data and link the username to the user profile
		if ( $column == 'booking_user' ) {
			$post        = get_post( $post_id );
			$bookingUser = get_user_by( 'id', $post->post_author );
			if ( $bookingUser ) {
				echo '<a href="' . get_edit_user_link( $bookingUser->ID ) . '">' . commonsbooking_sanitizeHTML( $bookingUser->user_login ) . '</a>';
			} else {
				echo '-';
			}
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
					$output = '-';

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
				// removed the following colums to fix an issue where booking status was not
				// shown in booking list when added via backend editor.
				// 'post_date',
				// 'post_status',
			];

			if (
				property_exists( $post = get_post( $post_id ), $column ) && (
					! in_array( $column, $bookingColumns ) ||
					get_post_meta( $post_id, 'type', true ) == Timeframe::BOOKING_ID
				)
			) {

				// get translated label for post status
				if ( $column === 'post_status' ) {
					echo __( commonsbooking_sanitizeHTML( get_post_status_object( get_post_status( $post_id ) )->label ) );
				} else {
					echo __( commonsbooking_sanitizeHTML( $post->{$column} ) );
				}
			}
		}
	}

	/**
	 * @param \WP_Query $query
	 *
	 * @return void
	 */
	public function setCustomColumnSortOrder( \WP_Query $query ) {
		if ( ! parent::setCustomColumnSortOrder( $query ) ) {
			return;
		}

		switch ( $query->get( 'orderby' ) ) {
			case 'booking_user':
				$query->set( 'orderby', 'author' );
				break;
		}
	}


	/**
	 * Registers metaboxes for cpt.
	 */
	public function registerMetabox() {
		// do not render the metabox if the user is on the login page (not yet logged in)
		if ( ! is_user_logged_in() ) {
			return;
		}
		$cmb = new_cmb2_box(
			[
				'id'           => static::getPostType() . '-custom-fields',
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
	 *
	 * @return array
	 */
	protected function getCustomFields() {
		// We need static types, because german month names dont't work for datepicker
		$dateFormat = 'd/m/Y';
		if ( str_starts_with( get_locale(), 'de_' ) ) {
			$dateFormat = 'd.m.Y';
		}

		if ( str_starts_with( get_locale(), 'en_' ) ) {
			$dateFormat = 'm/d/Y';
		}

		$booking_user = get_user_by( 'ID', get_current_user_id() );

		// define form fields based on CMB2
		return array(
			array(
				'name' => esc_html__( 'Edit booking', 'commonsbooking' ),
				'desc' => '<div style="padding:20px; background-color:#efe05c"><p>' . commonsbooking_sanitizeHTML(
					__(
						'<h1>Notice</h1><p>In this view, you as an admin can create or modify existing bookings. Please use it with caution. <br>
				<ul>
                    <li>Click on the <strong>preview button on the right panel</strong> to view more booking details and to cancel the booking via the cancel button.</li>
                    <li>Click on the <strong>Submit booking</strong> button at the end of the page to submit a new booking.</li>
                </ul>
				<strong>Please note</strong>: Only a few basic checks against existing bookings are performed. Please be wary of overlapping bookings.
                </p>
				',
						'commonsbooking'
					) . '</p></div>'
				),
				'id'   => 'title-booking-hint',
				'type' => 'title',
			),
			array(
				'name'    => esc_html__( 'Item', 'commonsbooking' ),
				'id'      => 'item-id',
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name'    => esc_html__( 'Location', 'commonsbooking' ),
				'id'      => 'location-id',
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'    => esc_html__( 'Book full day', 'commonsbooking' ),
				'id'      => 'full-day',
				'type'    => 'checkbox',
				'desc'    => esc_html__( 'The booking should apply to the entire day(s)', 'commonsbooking' ),
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => '<br>' . esc_html__( 'Set the start date. You must set the time to 00:00 if you want to book the full day ', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_START,
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
				'default'     => '00:00',
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				),
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => '<br>' . esc_html__( 'Set the end date. You must set time to 23:59 if you want to book the full day', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_END,
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
				'default'     => '23:59',
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				),
			),
			array(
				'name' => esc_html__( 'Booking Code', 'commonsbooking' ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'bookingcode',
				'type' => 'text',
				'desc' => esc_html__( 'Valid booking code will be automatically retrieved for bookings that apply to the full day.', 'commonsbooking' ),
			),
			array(
				'name'             => esc_html__( 'Booking User', 'commonsbooking' ),
				'id'               => 'booking_user',
				'type'             => 'user_ajax_search',
				'multiple-items'   => true,
				'default'          => array( self::class, 'getFrontendBookingUser' ),
				'desc'             => commonsbooking_sanitizeHTML(
					__(
						'Here you must select the user for whom the booking is made.<br>
                        If the booking was made by a user via frontend booking process, the user will be shown in this field.
                        <br><strong>Notice:</strong>The user will receive a booking confirmation as soon as the booking is submitted.',
						'commonsbooking'
					)
				),
			),
			array(
				'name'             => esc_html__( 'Admin Booking User', 'commonsbooking' ),
				'id'               => 'admin_booking_id',
				'type'             => 'select',
				'default'          => get_current_user_id(),
				'options'          => array(
					$booking_user->ID => $booking_user->get( 'user_nicename' ) . ' (' . $booking_user->first_name . ' ' . $booking_user->last_name . ')',
				),
				'attributes'       => array(
					'readonly' => true,
				),
				'desc'             => commonsbooking_sanitizeHTML(
					__(
						'This is the admin user who created or modified this booking.',
						'commonsbooking'
					)
				),
			),
			array(
				'name' => esc_html__( 'External comment', 'commonsbooking' ),
				'desc' => esc_html__( 'This comment can be seen by users in booking details. It can be set by users during the booking confirmation process if comments are enabled in settings.', 'commonsbooking' ),
				'id'   => 'comment',
				'type' => 'textarea_small',
			),
			array(
				'name' => esc_html__( 'Internal comment', 'commonsbooking' ),
				'desc' => esc_html__( 'This internal comment can only be seen in the backend by privileged users like admins or cb-managers', 'commonsbooking' ),
				'id'   => 'internal-comment',
				'type' => 'textarea_small',
			),
			array(
				'name'          => esc_html__( 'Submit booking', 'commonsbooking' ),
				'desc'          => esc_html__( 'This will create the specified booking and send out the booking confirmation email.', 'commonsbooking' ),
				'id'            => self::SUBMIT_BUTTON_ID,
				'type'          => 'text',
				'render_row_cb' => array( \CommonsBooking\View\Booking::class, 'renderSubmitButton' ),
			),
			array(
				'type'    => 'hidden',
				'id'      => 'prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) ),
			),
		);
	}

	/**
	 * Display permanent Admin notice on admin edit listing page for post type booking
	 *
	 * @return void
	 */
	public function displayBookingsAdminListNotice() {
		global $pagenow;

		$notice = commonsbooking_sanitizeHTML(
			__(
				'Bookings should be created via frontend booking calendar. <br>
		As an admin you can create bookings via this admin interface. Please be aware that admin bookings are not validated
		and checked. Use this function with care.<br>
		Click on preview to show booking details in frontend<br>
		To search and filter bookings please integrate the frontend booking list via shortcode.
		See here <a target="_blank" href="https://commonsbooking.org/?p=1433">How to display the booking list</a>',
				'commonsbooking'
			)
		);

		if ( ( $pagenow == 'edit.php' ) && isset( $_GET['post_type'] ) ) {
			if ( sanitize_text_field( $_GET['post_type'] ) == self::getPostType() ) {
				echo '<div class="notice notice-info"><p>' . commonsbooking_sanitizeHTML( $notice ) . '</p></div>';
			}
		}
	}


	/**
	 * Displays a permanent admin-notice if booking overlaps
	 *
	 * @return void
	 */
	public function displayOverlappingBookingNotice( $post ) {

		if ( get_transient( 'commonsbooking_booking_validation_failed_' . $post->ID ) ) {
			echo commonsbooking_sanitizeHTML( get_transient( 'commonsbooking_booking_validation_failed_' . $post->ID ) );
		}
	}

	/**
	 * Export user bookings using the supplied email. This is for integration with the WordPress personal data exporter.
	 *
	 * @param string $emailAddress
	 * @param $page
	 *
	 * @return array
	 */
	public static function exportUserBookingsByEmail( string $emailAddress, $page = 1 ): array {
		$page         = intval( $page );
		$itemsPerPage = 10;
		$exportItems  = array();
		// The internal group ID used by WordPress to group the data exported by this exporter.
		$groupID    = 'bookings';
		$groupLabel = __( 'CommonsBooking Bookings', 'commonsbooking' );

		$user = get_user_by( 'email', $emailAddress );
		if ( ! $user ) {
			return array(
				'data' => $exportItems,
				'done' => true,
			);
		}
		$bookings = \CommonsBooking\Repository\Booking::getForUserPaginated( $user, $page, $itemsPerPage );
		if ( ! $bookings ) {
			return array(
				'data' => $exportItems,
				'done' => true,
			);
		}
		foreach ( $bookings as $booking ) {
			$bookingID = $booking->ID;
			// exclude bookings that the user is eligible to see but are not their own
			// we are only concerned about one user's personal data
			if ( $booking->getUserData()->user_email !== $emailAddress ) {
				continue;
			}
			$bookingData = [
				[
					'name'  => __( 'Booking start', 'commonsbooking' ),
					'value' => $booking->pickupDatetime(),
				],
				[
					'name'  => __( 'Booking end', 'commonsbooking' ),
					'value' => $booking->returnDatetime(),
				],
				[
					'name'  => __( 'Time of booking', 'commonsbooking' ),
					'value' => Helper::FormattedDateTime( get_post_timestamp( $bookingID ) ),
				],
				[
					'name'  => __( 'Status', 'commonsbooking' ),
					'value' => $booking->getStatus(),
				],
				[
					'name'  => __( 'Booking code', 'commonsbooking' ),
					'value' => $booking->getBookingCode(),
				],
				[
					'name'  => __( 'Comment', 'commonsbooking' ),
					'value' => $booking->returnComment(),
				],
				[
					'name'  => __( 'Location', 'commonsbooking' ),
					'value' => $booking->getLocation()->post_title,
				],
				[
					'name'  => __( 'Item', 'commonsbooking' ),
					'value' => $booking->getItem()->post_title,
				],
				[
					'name'  => __( 'Time of cancellation', 'commonsbooking' ),
					'value' => $booking->getMeta( 'cancellation_time' ) ? Helper::FormattedDateTime( $booking->getMeta( 'cancellation_time' ) ) : '',
				],
				[
					'name'  => __( 'Admin booking by', 'commonsbooking' ),
					'value' => $booking->getMeta( 'admin_booking_id' ) ? get_user_by( 'id', $booking->getMeta( 'admin_booking_id' ) )->display_name : '',
				],
			];

			$exportItems[] = [
				'group_id'    => $groupID,
				'group_label' => $groupLabel,
				'item_id'     => $bookingID,
				'data'        => $bookingData,
			];
		}
		$done = count( $bookings ) < $itemsPerPage;
		return array(
			'data' => $exportItems,
			'done' => $done,
		);
	}

	/**
	 * Remove user bookings using the supplied email. This is for integration with the WordPress personal data eraser.
	 *
	 * @param string $emailAddress The email address
	 * @param $page This parameter has no real use in this function, we just use it to stick to WordPress expected parameters.
	 *
	 * @return array
	 */
	public static function removeUserBookingsByEmail( string $emailAddress, $page = 1 ): array {
		// we reset the page to 1, because we are deleting our results as we go. Therefore, increasing the page number would skip some results.
		$page         = 1;
		$itemsPerPage = 10;
		$removedItems = false;

		$user = get_user_by( 'email', $emailAddress );
		if ( ! $user ) {
			return array(
				'items_removed'  => $removedItems,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$bookings = \CommonsBooking\Repository\Booking::getForUserPaginated( $user, $page, $itemsPerPage );
		if ( ! $bookings ) {
			return array(
				'items_removed'  => $removedItems,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		foreach ( $bookings as $booking ) {
			$bookingID = $booking->ID;
			// exclude bookings that the user is eligible to see but are not their own
			// we are only concerned about one user's personal data
			if ( $booking->getUserData()->user_email !== $emailAddress ) {
				continue;
			}
			// Cancel the booking before deletion so that status change emails are sent
			$booking->cancel();
			// Delete the booking
			wp_delete_post( $bookingID, true );
			$removedItems = true;
		}

		$done = count( $bookings ) < $itemsPerPage;
		return array(
			'items_removed'  => $removedItems,
			'items_retained' => false, // always false, we don't retain any data
			'messages'       => array(),
			'done'           => $done,
		);
	}

	/**
	 * Returns the user that a specific booking is for if booking exists, otherwise returns current user.
	 * The post_author of a booking is always who the booking is for but not always the one who MADE the booking.
	 * A booking can be created by an admin but still be for a different user.
	 * This is helper function
	 *
	 * @return int|string
	 */
	public static function getFrontendBookingUser() {
		global $post;
		if ( $post ) {
			$authorID = $post->post_author;
		} else {
			$authorID = get_current_user_id();
		}
		return $authorID;
	}
}
