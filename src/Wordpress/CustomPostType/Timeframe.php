<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use WP_Post;
use Exception;
use CommonsBooking\View\Calendar;
use CommonsBooking\View\Admin\Filter;
use CommonsBooking\Messages\AdminMessage;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\UserRepository;

class Timeframe extends CustomPostType {

	/**
	 * "Opening Hours" timeframe type id.
	 */
	const OPENING_HOURS_ID = 1;

	/**
	 * "Bookable" timeframe type id.
	 */
	const BOOKABLE_ID = 2;

	/**
	 * "Holidays" timeframe type id.
	 */
	const LOCATION_CLOSED_ID = 3;

	/**
	 * "Repair" timeframe type id.
	 */
	const REPAIR_ID = 5;

	/**
	 * "Booking" timeframe type id.
	 */
	const BOOKING_ID = 6;

	/**
	 * "Booking canceled" timeframe type id.
	 */
	const BOOKING_CANCELED_ID = 7;

	/**
	 * Default value for possible advance booking days.
	 */
	const ADVANCE_BOOKING_DAYS = 31;

	/**
	 * CPT type.
	 * @var string
	 */
	public static $postType = 'cb_timeframe';

	public static function getSimilarPostTypes () {
		return [
			Timeframe::$postType,
			\CommonsBooking\Wordpress\CustomPostType\Booking::$postType
		];
    }

	/**
	 * Timeframetypes which cannot be "overbooked".
	 * @var int[]
	 */
	public static $multiDayBlockingFrames = [
		self::REPAIR_ID,
		self::BOOKING_ID,
	];

	/**
	 * Position in backend menu.
	 * @var int
	 */
	protected $menuPosition = 1;

	/**
	 * @var array
	 */
	protected $types;


	public function __construct() {
		$this->types = self::getTypes();

		/**
		 * Backend listing columns.
		 * @var string[]
		 */
		$this->listColumns = [
			'timeframe-author'                              => esc_html__( 'User', 'commonsbooking' ),
			'type'                                          => esc_html__( 'Type', 'commonsbooking' ),
			'item-id'                                       => esc_html__( 'Item', 'commonsbooking' ),
			'location-id'                                   => esc_html__( 'Location', 'commonsbooking' ),
			'post_date'                                     => esc_html__( 'Bookingdate', 'commonsbooking' ),
			'repetition-start'                              => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END => esc_html__( 'End Date', 'commonsbooking' ),
			'post_status'                                   => esc_html__( 'Booking Status', 'commonsbooking' ),
		];



		// List settings
		$this->removeListDateColumn();
	}

	/**
	 * Returns timeframe types.
	 * @return array
	 */
	public static function getTypes() {
		return [
			// Opening Hours disabled as its not implemented yet
			//self::OPENING_HOURS_ID    => esc_html__("Opening Hours", 'commonsbooking'),
			self::BOOKABLE_ID         	=> esc_html__( "Bookable", 'commonsbooking' ),
			self::LOCATION_CLOSED_ID    => esc_html__( "Location closed", 'commonsbooking' ),
			//self::REPAIR_ID           => esc_html__( "Repair", 'commonsbooking' ),
			self::BOOKING_ID            => esc_html__( "Booking", 'commonsbooking' ),
			self::BOOKING_CANCELED_ID   => esc_html__( "Booking canceled", 'commonsbooking' ),
		];
	}

	/**
	 * Returns array with repetition options.
	 * @return array
	 */
	public static function getTimeFrameRepetitions() {
		return [
			'norep' => esc_html__( "No Repetition", 'commonsbooking' ),
			'd'     => esc_html__( "Daily", 'commonsbooking' ),
			'w'     => esc_html__( "Weekly", 'commonsbooking' ),
			'm'     => esc_html__( "Monthly", 'commonsbooking' ),
			'y'     => esc_html__( "Yearly", 'commonsbooking' ),
		];
	}

	/**
	 * Retuns grid options.
	 * @return array
	 */
	public static function getGridOptions() {
		return [
			0 => esc_html__( "Full slot", 'commonsbooking' ),
			1 => esc_html__( "Hourly", 'commonsbooking' ),
		];
	}

	/**
	 * Returns true, if timeframe is of type booking.
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public static function isOfTypeBooking( $field ) {
		return get_post_meta( $field->object_id, 'type', true ) == self::BOOKING_ID;
	}

	/**
	 * Callback function for booking code list.
	 *
	 * @param $field_args
	 * @param $field
	 */
	public static function renderBookingCodeList( $field_args, $field ) {
		\CommonsBooking\View\BookingCodes::renderTable( $field->object_id() );
	}

	/**
	 * Priorities:
	 * 1 => esc_html__("Opening Hours", 'commonsbooking'),
	 * 2 => esc_html__("Bookable", 'commonsbooking'),
	 * 3 => esc_html__("Holidays", 'commonsbooking'),
	 * 5 => esc_html__("Repair", 'commonsbooking'),
	 * 6 => esc_html__("Booking", 'commonsbooking'),
	 * 7 => esc_html__("Booking canceled", 'commonsbooking')
	 *
	 * @param WP_Post $timeframeOne
	 * @param WP_Post $timeframeTwo
	 *
	 * @return WP_Post
	 */
	public static function getHigherPrioFrame( WP_Post $timeframeOne, WP_Post $timeframeTwo ) {
		$prioMapping = [
			self::REPAIR_ID        => 10,
			self::BOOKING_ID       => 9,
			self::LOCATION_CLOSED_ID      => 8,
			self::BOOKABLE_ID      => 6,
			self::OPENING_HOURS_ID => 5,
		];

		$typeOne = get_post_meta( $timeframeOne->ID, 'type', true );
		$typeTwo = get_post_meta( $timeframeTwo->ID, 'type', true );

		return $prioMapping[ $typeOne ] > $prioMapping[ $typeTwo ] ? $timeframeOne : $timeframeTwo;
	}

	/**
	 * Checks if timeframe is locked, so that an item cannot get booked.
	 *
	 * @param WP_Post $timeframe
	 *
	 * @return bool
	 */
	public static function isLocked( WP_Post $timeframe ) {
		$lockedTypes = [
			self::REPAIR_ID,
			self::LOCATION_CLOSED_ID,
			self::BOOKING_ID,
		];

		return in_array( get_post_meta( $timeframe->ID, 'type', true ), $lockedTypes );
	}

	/**
	 * Returns true if frame is overbookable.
	 *
	 * @param WP_Post $timeframe
	 *
	 * @return bool
	 */
	public static function isOverBookable( WP_Post $timeframe ) {
		return ! in_array( get_post_meta( $timeframe->ID, 'type', true ), self::$multiDayBlockingFrames );
	}

	/**
	 * Returns view-class.
	 * @return \CommonsBooking\View\Timeframe
	 */
	public static function getView() {
		// @TODO implement view.
	}

	/**
	 * Adds filter dropdown // filter by type (eg. bookable, repair etc.) in timeframe List
	 *
	 * @return void
	 */
	public static function addAdminTypeFilter() {
		Filter::renderFilter(
			static::$postType,
			esc_html__( 'Filter By Type ', 'commonsbooking' ),
			'filter_type',
			static::getTypes()
		);
	}

	/**
	 * Adds filter dropdown // filter by item in timeframe List
	 */
	public static function addAdminItemFilter() {
		$items = \CommonsBooking\Repository\Item::get(
			[
				'post_status' => 'any',
				'orderby'     => 'post_title',
				'order'       => 'asc',
				'nopaging'    => true
			]
		);
		if ( $items ) {
			$values = [];
			foreach ( $items as $item ) {
				$values[ $item->ID ] = $item->post_title;
			}

			Filter::renderFilter(
                static::$postType,
				esc_html__( 'Filter By Item ', 'commonsbooking' ),
				'filter_item',
				$values
			);
		}
	}

	/**
	 * Adds filter dropdown // filter by location in timeframe List
	 */
	public static function addAdminLocationFilter() {
		$locations = \CommonsBooking\Repository\Location::get(
			[
				'post_status' => 'any',
				'orderby'     => 'post_title',
				'order'       => 'asc',
				'nopaging'    => true
			]
		);
		if ( $locations ) {
			$values = [];
			foreach ( $locations as $location ) {
				$values[ $location->ID ] = $location->post_title;
			}

			Filter::renderFilter(
				static::$postType,
				esc_html__( 'Filter By Location ', 'commonsbooking' ),
				'filter_location',
				$values
			);
		}
	}

	/**
	 * Adds filter dropdown // filter by location in timeframe List
	 */
	public static function addAdminStatusFilter() {
		$values = [];
		foreach ( \CommonsBooking\Model\Booking::$bookingStates as $bookingState ) {
			$values[ $bookingState ] = $bookingState;
		}
		Filter::renderFilter(
			static::$postType,
			esc_html__( 'Filter By Status ', 'commonsbooking' ),
			'filter_post_status',
			$values
		);
	}

	/**
	 * Adds filter dropdown // filter by location in timeframe List
	 */
	public static function addAdminDateFilter() {
		$startDateInputName = 'admin_filter_startdate';
		$endDateInputName   = 'admin_filter_enddate';

		$from = ( isset( $_GET[ $startDateInputName ] ) && $_GET[ $startDateInputName ] ) ? sanitize_text_field( $_GET[ $startDateInputName ] ) : '';
		$to   = ( isset( $_GET[ $endDateInputName ] ) && $_GET[ $endDateInputName ] ) ? sanitize_text_field( $_GET[ $endDateInputName ] ) : '';

		Filter::renderDateFilter(
			static::$postType,
			$startDateInputName,
			$endDateInputName,
			$from,
			$to
		);
	}

	/**
	 * Filters admin list by type (e.g. bookable, repair etc. )
	 *
	 * @param  (wp_query object) $query
	 *
	 * @return Void
	 */
	public static function filterAdminList( $query ) {
		global $pagenow;

		if (
			is_admin() && $query->is_main_query() &&
			isset( $_GET['post_type'] ) && static::$postType == $_GET['post_type'] &&
			$pagenow == 'edit.php'
		) {
			// Meta value filtering
			$query->query_vars['meta_query'] = array(
				'relation' => 'AND',
			);
			$meta_filters                    = [
				'type'        => 'admin_filter_type',
				'item-id'     => 'admin_filter_item',
				'location-id' => 'admin_filter_location',
			];
			foreach ( $meta_filters as $key => $filter ) {
				if (
					isset( $_GET[ $filter ] ) &&
					$_GET[ $filter ] != ''
				) {
					$query->query_vars['meta_query'][] = array(
						'key'   => $key,
						'value' => sanitize_text_field( $_GET[ $filter ] ),
					);
				}
			}

			// Timerange filtering
			// Start date
			if (
				isset( $_GET['admin_filter_startdate'] ) &&
				$_GET['admin_filter_startdate'] != ''
			) {
				$query->query_vars['meta_query'][] = array(
					'key'     => 'repetition-start',
					'value'   => strtotime( sanitize_text_field( $_GET['admin_filter_startdate'] ) ),
					'compare' => ">=",
				);
			}

			// End date
			if (
				isset( $_GET['admin_filter_enddate'] ) &&
				$_GET['admin_filter_enddate'] != ''
			) {
				$query->query_vars['meta_query'][] = array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_END,
					'value'   => strtotime( sanitize_text_field( $_GET['admin_filter_enddate'] ) ),
					'compare' => "<=",
				);
			}

			// Post field filtering
			$post_filters = [
				'post_status' => 'admin_filter_post_status',
			];
			foreach ( $post_filters as $key => $filter ) {
				if (
					isset( $_GET[ $filter ] ) &&
					$_GET[ $filter ] != ''
				) {
					$query->query_vars[ $key ] = sanitize_text_field( $_GET[ $filter ] );
				}
			}

			// Check if current user is allowed to see posts
			if ( ! commonsbooking_isCurrentUserAdmin() ) {
				$locations = \CommonsBooking\Repository\Location::getByCurrentUser();
				array_walk( $locations, function ( &$item, $key ) {
					$item = $item->ID;
				} );
				$items = \CommonsBooking\Repository\Item::getByCurrentUser();
				array_walk( $items, function ( &$item, $key ) {
					$item = $item->ID;
				} );

				$query->query_vars['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'location-id',
						'value'   => $locations,
						'compare' => 'IN'
					),
					array(
						'key'     => 'item-id',
						'value'   => $items,
						'compare' => 'IN'
					),
				);
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
				'title'        => esc_html__( 'Timeframe', 'commonsbooking' ),
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
				'name'    => esc_html__( 'Type', 'commonsbooking' ),
				'desc'    => esc_html__( 'Select Type of this timeframe (e.g. bookable, repair, holidays, booking). See Documentation for detailed information.', 'commonsbooking' ),
				'id'      => "type",
				'type'    => 'select',
				'options' => self::getTypes(),
			),
			array(
				'name'    => esc_html__( "Location", 'commonsbooking' ),
				'id'      => "location-id",
				'type'    => 'select',
				'show_option_none' => esc_html__( 'Please select' , 'commonsbooking'),
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'    => esc_html__( "Item", 'commonsbooking' ),
				'id'      => "item-id",
				'type'    => 'select',
				'show_option_none' => esc_html__( 'Please select' , 'commonsbooking'),
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name'       => esc_html__( 'Maximum booking duration', 'commonsbooking' ),
				'desc'       => esc_html__( 'Maximum booking duration in days', 'commonsbooking' ),
				'id'         => "timeframe-max-days",
				'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				'type'       => 'text_small',
				'attributes' => array(
					'type' => 'number',
					'min'  => '1',
				),
				'default'    => 3,
			),
			array(
				'name'       => esc_html__( 'Maximum booking days in advance', 'commonsbooking' ),
				'desc'       => esc_html__( 'Select for how many days in advance the calendar should display bookable days. Calculated from the current date.', 'commonsbooking' ),
				'id'         => "timeframe-advance-booking-days",
				'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				'type'       => 'text_small',
				'attributes' => array(
					'type' => 'number',
					'min'  => '1',
				),
				'default'    => self::ADVANCE_BOOKING_DAYS,
			),
			array(
				'name'    => esc_html__( "Restrict bookings to user roles", 'commonsbooking' ),
				'id'      => "allowed_user_roles",
				'desc'    => esc_html__( 'Select one or more user roles to restrict bookings based on these timeframe configuration to these user roles. Leave empty for no restrictions', 'commonsbooking' ),
				'type'    => 'pw_multiselect',
				'options' => self::sanitizeOptions( UserRepository::getUserRoles() ),
			),
			array(
				'name' => esc_html__( "Configure timeframe", 'commonsbooking' ),
				'id'   => "title-timeframe-config",
				'type' => 'title',
			),
			array(
				'name'    => esc_html__( 'Timeframe Repetition', 'commonsbooking' ),
				'desc'    => esc_html__(
					'Choose whether the time frame should repeat at specific intervals. The repetitions refer to the unit of a day. With the start and end date you define when the repetition interval starts and ends. If you choose "weekly", you can select specific days of the week below. Read the documentation for more information and examples.'
					, 'commonsbooking' ),
				'id'      => "timeframe-repetition",
				'type'    => 'select',
				'options' => self::getTimeFrameRepetitions(),
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
				'desc'    => commonsbooking_sanitizeHTML( __( 'Choose whether users can only select the entire from/to time period when booking (full slot) or book within the time period in an hourly grid. See the documentation: <a target="_blank" href="https://commonsbooking.org/?p=437">Manage Booking Timeframes</a>', 'commonsbooking' ) ),
				'id'      => "grid",
				'type'    => 'select',
				'options' => self::getGridOptions(),
			),
			array(
				'name' => esc_html__( "Configure repetition", 'commonsbooking' ),
				'desc' => esc_html__( 'Below you can make settings regarding the time frame repetition. ', 'commonsbooking' ),
				'id'   => "title-timeframe-rep-config",
				'type' => 'title',
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
				'name' => esc_html__( "Booking Codes", 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML( __( 'You can automatically generate booking codes. Codes can be generated only with the following settings:</br>
				- Whole day is enabled</br>
				- Start date and end date are set</br>
				<a href="https://commonsbooking.org/?p=437" target="_blank">More Information in the documentation</a>
				', 'commonsbooking' ) ),
				'id'   => "title-timeframe-booking-codes",
				'type' => 'title',
			),
			array(
				'name' => esc_html__( 'Create Booking Codes', 'commonsbooking' ),
				'desc' => esc_html__( 'Select to generate booking codes for each day within the start/end date. The booking codes will be generated after clicking "Save / Update".', 'commonsbooking' ),
				'id'   => "create-booking-codes",
				'type' => 'checkbox',
			),
			array(
				'name' => esc_html__( 'Show Booking Codes', 'commonsbooking' ),
				'desc' => esc_html__( 'Select whether users should be shown a booking code when booking.', 'commonsbooking' ),
				'id'   => "show-booking-codes",
				'type' => 'checkbox',
			),
			array(
				'name'          => esc_html__( 'Booking Codes', 'commonsbooking' ),
				'id'            => 'booking-codes-list',
				'type'          => 'title',
				'render_row_cb' => array( self::class, 'renderBookingCodeList' ),
				// function should return a bool value
			),
			array(
				'type'    => 'hidden',
				'id'      => 'prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) )
			),
		);
	}

	/**
	 * Save the new Custom Fields values
	 */
	public function savePost( $post_id, WP_Post $post ) {
		// This is just for timeframes
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

		// Validate timeframe
		$isValid = $this->validateTimeFrame( $post_id, $post );

		if ( $isValid ) {
			$timeframe          = new \CommonsBooking\Model\Timeframe( $post_id );
			$createBookingCodes = get_post_meta( $post_id, 'create-booking-codes', true );

			if ( $createBookingCodes == "on" && $timeframe->bookingCodesApplieable() ) {
				BookingCodes::generate( $post_id );
			}
		}
	}

	/**
	 * Validates timeframe and sets state to draft if invalid.
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @return bool
	 */
	protected function validateTimeFrame( $post_id, $post ): bool {
		try {
			$timeframe = new \CommonsBooking\Model\Timeframe( $post_id );
			if ( ! $timeframe->isValid() ) {
				// set post_status to draft if not valid
				if ( $post->post_status !== 'draft' ) {
					$post->post_status = 'draft';
					wp_update_post( $post );
				}

				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns CPT arguments.
	 * @return array
	 */
	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Timeframes', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Timeframe', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new timeframe', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit timeframe', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new timeframe', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show timeframe', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show timeframes', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search timeframes', 'commonsbooking' ),
			'not_found'             => esc_html__( 'Timeframes not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No timeframes found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent timeframes:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All timeframes', 'commonsbooking' ),
			'archives'              => esc_html__( 'Timeframe archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Timeframe attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to timeframe', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to timeframe', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Timeframe image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set timeframe image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove timeframe image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as timeframe image', 'commonsbooking' ),
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
			echo '<a href="' . get_edit_user_link( $timeframe_user->ID ) . '">' . $timeframe_user->user_login . '</a>';
		}


		if ( $value = get_post_meta( $post_id, $column, true ) ) {
			switch ( $column ) {
				case 'location-id':
				case 'item-id':
					if ( $post = get_post( $value ) ) {
						if ( get_post_type( $post ) == Location::getPostType() || get_post_type(
							                                                          $post
						                                                          ) == Item::getPostType() ) {
							echo $post->post_title;
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
					echo $output;
					break;
				case 'repetition-start':
				case \CommonsBooking\Model\Timeframe::REPETITION_END:
					echo date( 'd.m.Y H:i', $value );
					break;
				default:
					echo $value;
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
				echo $post->{$column};
			}
		}
	}

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		add_action( 'save_post_' . self::$postType, array( $this, 'savePost' ), 1, 2 );

		// Add type filter to backend list view
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminStatusFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminDateFilter' ) );
		add_action( 'pre_get_posts', array( self::class, 'filterAdminList' ) );

		// Listing of available items/locations
		add_shortcode( 'cb_items_table', array( Calendar::class, 'renderTable' ) );
	}
}
