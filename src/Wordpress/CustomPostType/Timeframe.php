<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Exception\BookingCodeException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Model\BookingCode;
use WP_Post;
use Exception;
use CommonsBooking\View\Calendar;
use CommonsBooking\View\Admin\Filter;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Service\Holiday;

/**
 * Configures WordPress custom post type for access in admin backend.
 * It holds default values for meta fields of initial installations.
 *
 * We use CMB2 text_datetime_timestamp fields, the meta fields for start and end date are stored in unix
 * timestamp (without timezone offset), when edited from admin backend.
 */
class Timeframe extends CustomPostType {

	/**
	 * "Opening Hours" timeframe type id.
	 * This type of timeframe is @depreacted .
	 */
	const OPENING_HOURS_ID = 1;

	/**
	 * "Bookable" timeframe type id.
	 */
	const BOOKABLE_ID = 2;

	/**
	 * "Holidays" timeframe type id.
	 */
	const HOLIDAYS_ID = 3;

	/**
	 * "Official Holidays" timeframe type id.
	 */
	const OFF_HOLIDAYS_ID = 4;

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
	 *
	 * @var string
	 */
	public static $postType = 'cb_timeframe';

	/**
	 * Timeframetypes which cannot be "overbooked".
	 *
	 * @var int[]
	 */
	public static $multiDayBlockingFrames = [
		self::REPAIR_ID,
		self::BOOKING_ID,
	];
	/**
	 * Position in backend menu.
	 *
	 * @var int
	 */
	protected $menuPosition = 1;
	/**
	 * @var array
	 */
	protected $types;

	public function __construct() {
		$this->types = self::getTypes();

		$this->listColumns = [
			'timeframe-author'                                                   => esc_html__( 'User', 'commonsbooking' ),
			'type'                                                               => esc_html__( 'Type', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::META_ITEM_ID                        => esc_html__( 'Item', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::META_LOCATION_ID                    => esc_html__( 'Location', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_START                    => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END                      => esc_html__( 'End Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS => esc_html__( 'Max. Booking Duration', 'commonsbooking' ),
		];

		// List settings
		$this->removeListDateColumn();
	}

	/**
	 * Returns timeframe types as associative array. This can be used for timeframe selection in CMB2
	 *
	 * @param bool $includeAll - When toggled, will include the "All" Option as a selection option
	 *
	 * @return array
	 */
	public static function getTypes( bool $includeAll = false ): array {
		$typeOptions = [];
		if ( $includeAll ) {
			$typeOptions += [
				'all' => esc_html__( 'All timeframe types', 'commonsbooking' ),
			];
		}
		$typeOptions += [
			// Opening Hours disabled as its not implemented yet
			// self::OPENING_HOURS_ID    => esc_html__("Opening Hours", 'commonsbooking'),
			self::BOOKABLE_ID => esc_html__( 'Bookable', 'commonsbooking' ),
			self::HOLIDAYS_ID => esc_html__( 'Holidays or location closed', 'commonsbooking' ),
			// Off Holidays disabled as its not implemented yet
			// self::OFF_HOLIDAYS_ID     => esc_html__("Official Holiday", 'commonsbooking'),
			self::REPAIR_ID   => esc_html__( 'Blocked (not overbookable)', 'commonsbooking' ),
			self::BOOKING_ID  => esc_html__( 'Booking', 'commonsbooking' ),
		];

		return $typeOptions;
	}

	public static function getSimilarPostTypes() {
		return [
			self::$postType,
			Booking::$postType,
		];
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

	public static function renderDateSelector( $field_args, $field ) {
		?>
		<label for="cmb2_multiselect_datepicker">
			<?php echo commonsbooking_sanitizeHTML( __( 'Select Dates:', 'commonsbooking' ) ); ?>
		</label>
		<input type="text" id="cmb2_multiselect_datepicker">
		<br>
		<?php
	}

	/**
	 * Priorities:
	 * 1 => esc_html__("Opening Hours", 'commonsbooking'),
	 * 2 => esc_html__("Bookable", 'commonsbooking'),
	 * 3 => esc_html__("Holidays", 'commonsbooking'),
	 * 4 => esc_html__("Official Holiday", 'commonsbooking'),
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
			self::HOLIDAYS_ID      => 8,
			self::OFF_HOLIDAYS_ID  => 7,
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
			self::HOLIDAYS_ID,
			self::OFF_HOLIDAYS_ID,
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
	 * @inheritDoc
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
			static::getTypesforSelectField(),
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
				'nopaging'    => true,
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
				'nopaging'    => true,
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
	 * Adds filter dropdown // filter by location in booking list
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
	 * @param \WP_Query $query for admin list objects
	 *
	 * @return void
	 */
	public static function filterAdminList( $query ) {
		global $pagenow;

		if (
			is_admin() && $query->is_main_query() &&
			isset( $_GET['post_type'] ) && static::$postType == sanitize_text_field( $_GET['post_type'] ) &&
			$pagenow == 'edit.php'
		) {
			// Meta value filtering
			$query->query_vars['meta_query'] = array(
				'relation' => 'AND',
			);
			$meta_filters                    = [
				'type'                                            => 'admin_filter_type',
				\CommonsBooking\Model\Timeframe::META_ITEM_ID     => 'admin_filter_item',
				\CommonsBooking\Model\Timeframe::META_LOCATION_ID => 'admin_filter_location',
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

			// post status filtering

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

			// Timerange filtering
			// Start date
			if (
				isset( $_GET['admin_filter_startdate'] ) &&
				$_GET['admin_filter_startdate'] != ''
			) {
				$query->query_vars['meta_query'][] = array(
					'key'     => \CommonsBooking\Model\Timeframe::REPETITION_START,
					'value'   => strtotime( sanitize_text_field( $_GET['admin_filter_startdate'] ) ),
					'compare' => '>=',
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
					'compare' => '<=',
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
				'id'           => static::getPostType() . '-custom-fields',
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

		return array(
			array(
				'name' => esc_html__( 'Comment', 'commonsbooking' ),
				'desc' => esc_html__( 'This comment is internal for timeframes like bookable, repair, holiday. If timeframe is a booking this comment can be set by users during the booking confirmation process.', 'commonsbooking' ),
				'id'   => 'comment',
				'type' => 'textarea_small',
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Type', 'commonsbooking' ),
				'desc'    => esc_html__( 'Select Type of this timeframe: Bookable or Location Closed. See Documentation for detailed information.', 'commonsbooking' ),
				'id'      => 'type',
				'type'    => 'select',
				'options' => self::getTypesforSelectField(),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Location', 'commonsbooking' ),
				'id'      => \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE,
				'type'    => 'select',
				'options' => self::getSelectionOptions(),
				'default' => \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'             => esc_html__( 'Location Category Selection', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS,
				'type'             => 'multicheck',
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Location::getTerms() ),
				'select_all_button' => false,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'             => esc_html__( 'Location Selection', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Timeframe::META_LOCATION_ID,
				'type'             => 'select',
				'show_option_none' => esc_html__( 'Please select', 'commonsbooking' ),
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'       => esc_html__( 'Select one or more locations', 'commonsbooking' ),
				'id'         => \CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST,
				'type'       => 'multicheck',
				'options'    => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Item selection', 'commonsbooking' ),
				'id'      => \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
				'type'    => 'select',
				'options' => self::getSelectionOptions(),
				'default' => \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'       => esc_html__( 'Select one or more items', 'commonsbooking' ),
				'id'         => \CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST,
				'type'       => 'multicheck',
				'options'    => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'             => esc_html__( 'Item Category Selection', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS,
				'type'             => 'multicheck',
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Item::getTerms() ),
				'select_all_button' => false,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'             => esc_html__( 'Item selection', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Timeframe::META_ITEM_ID,
				'type'             => 'select',
				'show_option_none' => esc_html__( 'Please select', 'commonsbooking' ),
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name' => esc_html__( 'Configure bookings', 'commonsbooking' ),
				'id'   => 'title-bookings-config',
				'type' => 'title',
			),
			array(
				'name'       => esc_html__( 'Maximum', 'commonsbooking' ),
				'desc'       => esc_html__( 'days in a row', 'commonsbooking' ),
				'id'         => \CommonsBooking\Model\Timeframe::META_MAX_DAYS,
				'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				'type'       => 'text_small',
				'attributes' => array(
					'type' => 'number',
					'min'  => '1',
				),
				'default_value'    => \CommonsBooking\Model\Timeframe::MAX_DAYS_DEFAULT,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'       => esc_html__( 'Lead time:', 'commonsbooking' ),
				'desc'       => commonsbooking_sanitizeHTML( __( 'Enter the number of days that should be blocked for bookings as a booking lead time (calculated from the current day).', 'commonsbooking' ) ),
				'id'         => \CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET,
				'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				'type'       => 'text_small',
				'attributes' => array(
					'type' => 'number',
					'min'  => '0',
				),
				'default_value'    => 0,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'       => esc_html__( 'Calendar shows as bookable', 'commonsbooking' ),
				'desc'       => commonsbooking_sanitizeHTML( __( 'Select for how many days in advance the calendar should display bookable days. Calculated from the current date.', 'commonsbooking' ) ),
				'id'         => \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS,
				'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				'type'       => 'text_small',
				'attributes' => array(
					'type' => 'number',
					'min'  => '1',
				),
				'default_value'    => self::ADVANCE_BOOKING_DAYS,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Allowed for', 'commonsbooking' ),
				'id'      => \CommonsBooking\Model\Timeframe::META_ALLOWED_USER_ROLES,
				'desc'    => commonsbooking_sanitizeHTML( __( '<br> Select one or more user roles that will be allowed to book the item exclusively. <br> <b> Leave this blank to allow all users to book the item. </b>', 'commonsbooking' ) ),
				'type'    => 'pw_multiselect',
				'options' => self::sanitizeOptions( UserRepository::getUserRoles() ),
				'attributes' => array(
					'placeholder' => esc_html__( 'User roles', 'commonsbooking' ),
				),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name' => esc_html__( 'Configure timeframe', 'commonsbooking' ),
				'id'   => 'title-timeframe-config',
				'type' => 'title',
			),
			array(
				'name' => esc_html__( 'Full day', 'commonsbooking' ),
				'desc' => esc_html__(
					'If this option is selected, users can choose only whole days for pickup and return. No specific time slots for pickup or return are offered. Select this option if the pickup/return should be arranged personally between the location and the user. ',
					'commonsbooking'
				),
				'id'   => 'full-day',
				'type' => 'checkbox',
				'default_value' => '',
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Grid', 'commonsbooking' ),
				'desc'    => commonsbooking_sanitizeHTML( __( 'Choose whether users can only select the entire from/to time period when booking (full slot) or book within the time period in an hourly grid. See the documentation: <a target="_blank" href="https://commonsbooking.org/documentation/first-steps/booking-timeframes-manage/">Manage Booking Timeframes</a>', 'commonsbooking' ) ),
				'id'      => 'grid',
				'type'    => 'select',
				'options' => self::getGridOptions(),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'        => esc_html__( 'Start time', 'commonsbooking' ),
				'id'          => 'start-time',
				'type'        => 'text_time',
				'show_on_cb'  => 'cmb2_hide_if_no_cats', // function should return a bool value
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'stepMinute' => 60,
							'timeFormat' => 'HH:mm',
						)
					),
				),
				'time_format' => esc_html( get_option( 'time_format' ) ),
				'date_format' => $dateFormat,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'        => esc_html__( 'End time', 'commonsbooking' ),
				'id'          => 'end-time',
				'type'        => 'text_time',
				'timeFormat' => 'HH:mm',
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'stepMinute' => 60,
							'timeFormat' => 'HH:mm',
						)
					),
				),
				'time_format' => esc_html( get_option( 'time_format' ) ),
				'date_format' => $dateFormat,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Timeframe Repetition', 'commonsbooking' ),
				'desc'    => esc_html__(
					'Choose whether the time frame should repeat at specific intervals. The repetitions refer to the unit of a day. With the start and end date you define when the repetition interval starts and ends. If you choose "weekly", you can select specific days of the week below. Read the documentation for more information and examples.',
					'commonsbooking'
				),
				'id'      => \CommonsBooking\Model\Timeframe::META_REPETITION,
				'type'    => 'select',
				'options' => self::getTimeFrameRepetitions(),
				'default_cb' => 'commonsbooking_filter_from_cmb2',
				'default' => 'w',
			),
			array(
				'name' => esc_html__( 'Import holidays', 'commonsbooking' ),
				'desc' => esc_html__(
					'Select the year and state to import holidays for (as of now only German holidays are supported)',
					'commonsbooking'
				),
				'id'   => '_cmb2_holiday',
				'type' => 'holiday_get_fields',
			),
			array(
				'name' => esc_html__( 'Configure repetition', 'commonsbooking' ),
				'desc' => esc_html__( 'Below you can make settings regarding the time frame repetition. ', 'commonsbooking' ),
				'id'   => 'title-timeframe-rep-config',
				'type' => 'title',
			),
			array(
				'name'          => esc_html__( 'Selected manual dates', 'commonsbooking' ),
				'desc'          => commonsbooking_sanitizeHTML( __( 'Enter the dates in the YYYY-MM-DD format here, the dates are separated by a comma. <br> Example: 2023-05-24,2023-06-24 <br> You can also use the datepicker above to pick dates for this field.', 'commonsbooking' ) ),
				'id'            => \CommonsBooking\Model\Timeframe::META_MANUAL_SELECTION,
				'type'          => 'textarea_small',
				'before_row'    => array( self::class, 'renderDateSelector' ),
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the start date. If you have selected repetition, this is the start date of the interval. ', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_START,
				'type'        => 'text_date_timestamp',
				'time_format' => esc_html( get_option( 'time_format' ) ),
				'date_format' => $dateFormat,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'    => esc_html__( 'Weekdays', 'commonsbooking' ),
				'id'      => 'weekdays',
				'type'    => 'multicheck',
				'options' => [
					1 => esc_html__( 'Monday', 'commonsbooking' ),
					2 => esc_html__( 'Tuesday', 'commonsbooking' ),
					3 => esc_html__( 'Wednesday', 'commonsbooking' ),
					4 => esc_html__( 'Thursday', 'commonsbooking' ),
					5 => esc_html__( 'Friday', 'commonsbooking' ),
					6 => esc_html__( 'Saturday', 'commonsbooking' ),
					7 => esc_html__( 'Sunday', 'commonsbooking' ),
				],
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => commonsbooking_sanitizeHTML(
					__(
						'Set the end date. If you have selected repetition, this is the end date of the interval. Leave blank if you do not want to set an end date.
                <br><strong>Notice:</strong> If you want to select only one day (e.g. for holidays or blocked days) set the start and the end date to same day.',
						'commonsbooking'
					)
				),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_END,
				'type'        => 'text_date_timestamp',
				'time_format' => esc_html( get_option( 'time_format' ) ),
				'date_format' => $dateFormat,
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name' => esc_html__( 'Booking Codes', 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML(
					__(
						'You can automatically generate booking codes. Codes can be generated only with the following settings:</br>
				- Whole day is enabled</br>
				- Timeframe is bookable</br>
				<a href="https://commonsbooking.org/documentation/first-steps/booking-timeframes-manage/" target="_blank">More Information in the documentation</a>
				',
						'commonsbooking'
					)
				),
				'id'   => 'title-timeframe-booking-codes',
				'type' => 'title',
			),
			array(
				'name' => esc_html__( 'Create Booking Codes', 'commonsbooking' ),
				'desc' => esc_html__( 'Select to generate booking codes for each day within the start/end date. The booking codes will be generated after clicking "Save / Update".', 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES,
				'type' => 'checkbox',
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name' => esc_html__( 'Show Booking Codes', 'commonsbooking' ),
				'desc' => esc_html__( 'Select whether users should be shown a booking code when booking.', 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES,
				'type' => 'checkbox',
				'default_cb' => 'commonsbooking_filter_from_cmb2',
			),
			array(
				'name'          => esc_html__( 'Booking Codes', 'commonsbooking' ),
				'id'            => 'direct-email-booking-codes-list',
				'type'          => 'title',
				'render_row_cb' => [ '\CommonsBooking\View\BookingCodes', 'renderDirectEmailRow' ],
			),
			array(
				'name' => esc_html__( 'Send booking codes automated by email', 'commonsbooking' ),
				'desc_cb' => esc_html__( 'Enable automated sending of booking codes by email', 'commonsbooking' ),
				'name_start'        => esc_html__( 'Start Date', 'commonsbooking' ),
				'desc_start'        => commonsbooking_sanitizeHTML( __( 'First day to send Codes (List starts at next month)<br>(Same day will be used for subsequent messages) ', 'commonsbooking' ) ),
				'date_format_start' => $dateFormat,
				'default_start'     => strtotime( 'now' ),
				'name_nummonth'       => esc_html__( 'Months to send', 'commonsbooking' ),
				'desc_nummonth'       => esc_html__( "Send booking codes for this amount of month's in one email", 'commonsbooking' ),
				'default_nummonth'      => 1,
				'msg_next_email'        => esc_html__( 'Next email planned for: ', 'commonsbooking' ),
				'msg_email_not_planned'     => esc_html__( '(not planned)', 'commonsbooking' ),
				'id'   => \CommonsBooking\View\BookingCodes::CRON_EMAIL_CODES,
				'type' => 'booking_codes_email_fields',
				'sanitization_cb' => [ '\CommonsBooking\View\BookingCodes', 'sanitizeCronEmailCodes' ],
				'escape_cb'       => [ '\CommonsBooking\View\BookingCodes', 'escapeCronEmailCodes' ],
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
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) ),
			),
		);
	}

	/**
	 * Get allowed timeframe types for selection box in timeframe editor
	 * TODO: can be removed if type cleanup has been done (e.g. move BOOKIG_ID to Booking-Class and rename existing types )
	 *
	 * @return array
	 */
	public static function getTypesforSelectField() {
		$types = self::getTypes();

		// remove unused types
		unset(
			$types[ self::BOOKING_ID ],
			$types[ self::BOOKING_CANCELED_ID ],
			// $types[ self::REPAIR_ID ],
		);

		return $types;
	}

	/**
	 * Returns style of item / location selection
	 *
	 * @return array
	 */
	public static function getSelectionOptions(): array {
		$selection = [ \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID => esc_html__( 'Manual selection', 'commonsbooking' ) ];
		if ( commonsbooking_isCurrentUserAdmin() ) {
			$selection[ \CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID ] = esc_html__( 'Select by category', 'commonsbooking' );
			$selection[ \CommonsBooking\Model\Timeframe::SELECTION_ALL_ID ]      = esc_html__( 'All', 'commonsbooking' );
		}
		return $selection;
	}

	/**
	 * Returns grid options.
	 *
	 * @return array
	 */
	public static function getGridOptions() {
		return [
			0 => esc_html__( 'Full slot', 'commonsbooking' ),
			1 => esc_html__( 'Hourly', 'commonsbooking' ),
		];
	}

	/**
	 * Returns array with repetition options.
	 *
	 * @return array
	 */
	public static function getTimeFrameRepetitions() {
		return [
			'norep' => esc_html__( 'No repetition', 'commonsbooking' ),
			'manual' => esc_html__( 'Manual repetition', 'commonsbooking' ),
			'd'     => esc_html__( 'Daily', 'commonsbooking' ),
			'w'     => esc_html__( 'Weekly', 'commonsbooking' ),
			'm'     => esc_html__( 'Monthly', 'commonsbooking' ),
			'y'     => esc_html__( 'Yearly', 'commonsbooking' ),
		];
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

		// assign the startDate and EndDate for manual repetition (needs to be done before validation in order for validation to work)
		try {
			$timeframe = new \CommonsBooking\Model\Timeframe( $post_id );
		} catch ( Exception $e ) {
			set_transient(
				\CommonsBooking\Model\Timeframe::ERROR_TYPE,
				$e->getMessage(),
				45
			);
			return;
		}
		$timeframe->updatePostMetaStartAndEndDate();

		// Validate timeframe
		$isValid = self::validateTimeFrame( $timeframe );

		if ( $isValid ) {
			self::sanitizeRepetitionEndDate( $post_id );

			// Update postmeta related to dynamic selection fields
			self::manageTimeframeMeta( $post_id );

			// delete unused postmeta
			self::removeIrrelevantPostmeta( $timeframe );

			if ( $timeframe->usesBookingCodes() && $timeframe->bookingCodesApplicable() ) {
				try {
					BookingCodes::generate( $timeframe );
				} catch ( BookingCodeException $e ) {
					// unset checkboxes if booking codes could not be generated
					delete_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES );
					delete_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES );

					set_transient(
						BookingCode::ERROR_TYPE,
						$e->getMessage(),
						45
					);
				}
			}
		}
	}


	public function updatedPostMeta( $meta_id, $object_id, $meta_key, $meta_value ) {
		// make sure, that action is only executed if timeframe is changed
		if ( get_post( $object_id )->post_type !== self::getPostType() ) {
			return;
		}
		if ( $meta_key == \CommonsBooking\Model\Timeframe::META_LOCATION_ID ) { // Location ID was changed, the only evidence we still have is the item ID
			$correspondingItems = get_post_meta( $object_id, \CommonsBooking\Model\Timeframe::META_ITEM_ID );
			$item_id            = reset( $correspondingItems ); // value has to be reset in order to retrieve first value
			$orphanedBookings   = \CommonsBooking\Repository\Booking::getOrphaned( null, [ $item_id ] );
			if ( $orphanedBookings ) {
				foreach ( $orphanedBookings as $booking ) {
					update_post_meta( $booking->ID, \CommonsBooking\Model\Booking::META_LAST_TIMEFRAME, $object_id );
				}
				set_transient(
					\CommonsBooking\Model\Timeframe::ORPHANED_TYPE,
					/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
					commonsbooking_sanitizeHTML(
						__(
							'Orphaned bookings found, can migrate. <a href="admin.php?page=cb-mass-operations"> Click here to migrate </a>',
							'commonsbooking'
						)
					)
				);
			}
		}
	}

	/**
	 * Adds 23h 59m 59s to repetition end, to set the timestamp at the end of the day and not
	 * the very start.
	 *
	 * @param $postId
	 *
	 * @return void
	 */
	private static function sanitizeRepetitionEndDate( $postId ): void {
		$repetitionEnd = get_post_meta( $postId, \CommonsBooking\Model\Timeframe::REPETITION_END, true );
		if ( $repetitionEnd ) {
			$repetitionEnd = strtotime( '+23 Hours +59 Minutes +59 Seconds', $repetitionEnd );
			update_post_meta( $postId, \CommonsBooking\Model\Timeframe::REPETITION_END, $repetitionEnd );
		}
	}

	/**
	 * Validates timeframe and sets state to draft if invalid.
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return bool
	 */
	protected static function validateTimeFrame( $timeframe ): bool {
		try {
			$timeframe->isValid();
		} catch ( TimeframeInvalidException $e ) {
			set_transient(
				\CommonsBooking\Model\Timeframe::ERROR_TYPE,
				commonsbooking_sanitizeHTML( $e->getMessage() ),
				45
			);
			// set post_status to draft if not valid
			$post = $timeframe->getPost();
			if ( $post->post_status !== 'draft' ) {
				$post->post_status = 'draft';
				$postArr           = get_object_vars( $post );
				wp_update_post( $postArr );
			}
			return false;
		}

		return true;
	}

	/**
	 * Will update the dynamic item / location assignment for all timeframes.
	 * Only valid for timeframes which can have a dynamic selection type (so far only holidays and repair timeframes)
	 *
	 * @return void
	 */
	public static function updateAllTimeframes() {
		$timeframes = \CommonsBooking\Repository\Timeframe::get(
			[],
			[],
			[
				self::HOLIDAYS_ID,
				self::REPAIR_ID,
			]
		);
		foreach ( $timeframes as $timeframe ) {
			static::manageTimeframeMeta( $timeframe->ID );
		}
	}

	/**
	 * This function is for the timeframes which do not have specific item(s) or location(s) assigned
	 * but rather use a dynamic selection type like an entire category of items / locations or all items / locations.
	 * Since the count of items or locations that count as ALL can change without the timeframe changing, we need
	 * to constantly update the timeframes which have this setting.
	 *
	 * THIS FUNCTIONALITY IS THEORETICALLY IMPLEMENTED FOR ALL TIMEFRAMES, BUT ONLY TESTED AND AVAILABLE FOR HOLIDAYS.
	 *
	 * This should run in the following cases:
	 * 1. Item / Location is assigned / removed from category
	 * 2. Categories are re-ordered
	 * 3. Item / Location is removed entirely
	 * 4. Item / Location is added
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public static function manageTimeframeMeta( $post_id ) {
		$postModel = get_post( $post_id );
		// This is just for timeframes
		if ( $postModel->post_type !== static::getPostType() ) {
			return;
		}

		$timeframe             = new \CommonsBooking\Model\Timeframe( $post_id );
		$itemSelectionType     = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE ) );
		$locationSelectionType = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE ) );

		// we only need to update the timeframes which have the dynamic selection type
		if ( $itemSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID && $locationSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID ) {
			return;
		}

		if ( $itemSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID ) {
			$itemCategorySelection = $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS );
			$taxQuery              = array(
				'tax_query' => array(
					array(
						'taxonomy' => Item::getTaxonomyName(),
						'field' => 'term_id',
						'terms' => $itemCategorySelection,
					),
				),
			);
			$items                 = \CommonsBooking\Repository\Item::get( $taxQuery );
			// for some reason, the item ids need to be saved as strings
			$itemIds = array_map(
				function ( $item ) {
					return strval( $item->ID );
				},
				$items
			);
			update_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST, $itemIds );
		} elseif ( $itemSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_ALL_ID ) {
			$items = \CommonsBooking\Repository\Item::get();
			// for some reason, the item ids need to be saved as strings
			$itemIds = array_map(
				function ( $item ) {
					return strval( $item->ID );
				},
				$items
			);
			update_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST, $itemIds );
		}

		if ( $locationSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID ) {
			$locationCategorySelection = $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS );
			$taxQuery                  = array(
				'tax_query' => array(
					array(
						'taxonomy' => Location::getTaxonomyName(),
						'field' => 'term_id',
						'terms' => $locationCategorySelection,
					),
				),
			);
			$locations                 = \CommonsBooking\Repository\Location::get( $taxQuery );
			// for some reason, the location ids need to be saved as strings
			$locationIds = array_map(
				function ( $location ) {
					return strval( $location->ID );
				},
				$locations
			);
			update_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST, $locationIds );
		} elseif ( $locationSelectionType === \CommonsBooking\Model\Timeframe::SELECTION_ALL_ID ) {
			$locations = \CommonsBooking\Repository\Location::get();
			// for some reason, the location ids need to be saved as strings
			$locationIds = array_map(
				function ( $location ) {
					return strval( $location->ID );
				},
				$locations
			);
			update_post_meta( $post_id, \CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST, $locationIds );
		}
	}

	/**
	 * For different types of timeframes, different types of postmeta is relevant.
	 * This function removes the postmeta irrelevant for the current type from the post.
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return void
	 */
	public static function removeIrrelevantPostmeta( \CommonsBooking\Model\Timeframe $timeframe ) {
		$onlyRelevantForBookable = [
			\CommonsBooking\Model\Timeframe::META_MAX_DAYS,
			\CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS,
			\CommonsBooking\Model\Timeframe::META_ALLOWED_USER_ROLES,
			\CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET,
			\CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES,
			\CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES,
		];
		// remove multi-select postmeta if not relevant (#507)
		$onlyRelevantForHolidays = [
			\CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST,
			\CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST,
			\CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS,
			\CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE,
		];

		if ( $timeframe->getType() != self::BOOKABLE_ID ) {
			foreach ( $onlyRelevantForBookable as $metaKey ) {
				delete_post_meta( $timeframe->ID, $metaKey );
			}
		}

		if ( $timeframe->getType() != self::HOLIDAYS_ID ) {
			foreach ( $onlyRelevantForHolidays as $metaKey ) {
				delete_post_meta( $timeframe->ID, $metaKey );
			}
			// reset to manual selection
			update_post_meta( $timeframe->ID, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );
			update_post_meta( $timeframe->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );
		}
	}

	/**
	 * Returns CPT arguments.
	 *
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
			// oder die Standar-Werte post und page in form eines Strings gesetzt werden
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

			'show_in_rest' => true,
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
		if ( $column == 'timeframe-author' ) {
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
					echo date( 'd.m.Y', $value );
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
					get_post_meta( $post_id, 'type', true ) == self::BOOKING_ID
				)
			) {
				echo commonsbooking_sanitizeHTML( $post->{$column} );
			}
		}
	}

	/**
	 * @param \WP_Query $query
	 *
	 * @return true|void returns true if inheriting method should set sort order, void if this method sets it
	 */
	public function setCustomColumnSortOrder( \WP_Query $query ) {
		if ( ! parent::setCustomColumnSortOrder( $query ) ) {
			return;
		}

		switch ( $query->get( 'orderby' ) ) {
			case 'item-id':
				add_filter(
					'posts_join',
					function ( $join ) {
						global $wp_query, $wpdb;

						if ( ! empty( $wp_query->query_vars['orderby'] ) && $wp_query->query_vars['orderby'] === \CommonsBooking\Model\Timeframe::META_ITEM_ID ) {
							$join .= "LEFT JOIN $wpdb->postmeta joined_meta_items "
								. "ON $wpdb->posts.ID = joined_meta_items.post_id AND joined_meta_items.meta_key = '" . \CommonsBooking\Model\Timeframe::META_ITEM_ID . "' ";
							$join .= "JOIN $wpdb->posts joined_items ON joined_meta_items.meta_value = joined_items.ID ";
						}

						return $join;
					}
				);
				add_filter(
					'posts_orderby',
					function ( $orderby ) {
						global $wp_query;

						if ( ! empty( $wp_query->query_vars['orderby'] ) && $wp_query->query_vars['orderby'] === \CommonsBooking\Model\Timeframe::META_ITEM_ID ) {
							$orderby = 'joined_items.post_title ' . $wp_query->query_vars['order'];
						}

						return $orderby;
					}
				);
				break;
			case 'location-id':
				add_filter(
					'posts_join',
					function ( $join ) {
						global $wp_query, $wpdb;

						if ( ! empty( $wp_query->query_vars['orderby'] ) && $wp_query->query_vars['orderby'] === \CommonsBooking\Model\Timeframe::META_LOCATION_ID ) {
							$join .= "LEFT JOIN $wpdb->postmeta joined_meta_locations "
								. "ON $wpdb->posts.ID = joined_meta_locations.post_id AND joined_meta_locations.meta_key = '" . \CommonsBooking\Model\Timeframe::META_LOCATION_ID . "' ";
							$join .= "JOIN $wpdb->posts joined_locations ON joined_meta_locations.meta_value = joined_locations.ID ";
						}

						return $join;
					}
				);
				add_filter(
					'posts_orderby',
					function ( $orderby ) {
						global $wp_query;

						if ( ! empty( $wp_query->query_vars['orderby'] ) && $wp_query->query_vars['orderby'] === \CommonsBooking\Model\Timeframe::META_LOCATION_ID ) {
							$orderby = 'joined_locations.post_title ' . $wp_query->query_vars['order'];
						}

						return $orderby;
					}
				);
				break;
			case 'type':
				$query->set( 'meta_key', 'type' );
				$query->set( 'orderby', 'meta_value' );
				break;
			case \CommonsBooking\Model\Timeframe::REPETITION_START:
			case \CommonsBooking\Model\Timeframe::REPETITION_END:
				$query->set( 'meta_key', $query->get( 'orderby' ) );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			default:
				// this means, that further sorting is done by the inheriting method
				return true;
		}
	}


	/**
	 * Initiates needed hooks.
	 *
	 * @return void
	 */
	public function initHooks() {
		// Add custom cmb2 type for email booking codes by cron
		add_action( 'cmb2_render_booking_codes_email_fields', [ '\CommonsBooking\View\BookingCodes','renderCronEmailFields' ], 10, 5 );
		add_action( 'cmb2_save_field_' . \CommonsBooking\View\BookingCodes::CRON_EMAIL_CODES, [ '\CommonsBooking\View\BookingCodes','cronEmailCodesSaved' ], 10, 3 );
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		// must be 'save_post' only because of priority in relation to cmb2
		add_action( 'save_post', array( $this, 'savePost' ), 11, 2 );

		add_action( 'updated_post_meta', array( $this, 'updatedPostMeta' ), 11, 4 );
		// Add type filter to backend list view
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminDateFilter' ) );
		add_action( 'pre_get_posts', array( self::class, 'filterAdminList' ) );

		// Listing of available items/locations
		add_shortcode( 'cb_items_table', array( Calendar::class, 'shortcode' ) );

		// rendering callback for field with id _cmb2_holiday
		add_filter( 'cmb2_render_holiday_get_fields', array( Holiday::class, 'renderFields' ), 10, 5 );
	}
}
