<?php

namespace CommonsBooking\View\Admin;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Wordpress\CustomPostType\Item as ItemCPT;
use CommonsBooking\Wordpress\CustomPostType\Location as LocationCPT;
use CommonsBooking\Wordpress\CustomPostType\Timeframe as TimeframeCPT;

/**
 * Multi-step creation wizard for Items, Locations and Timeframes.
 */
class AvailabilityWizard {

	/**
	 * Allowed post types for the quick-create AJAX handler.
	 *
	 * @var string[]
	 */
	private static array $allowedPostTypes = [
		'cb_item',
		'cb_location',
		'cb_timeframe',
	];

	/**
	 * Renders the 3-step wizard shell page.
	 *
	 * Called by add_submenu_page() as the page callback.
	 *
	 * @return void
	 */
	public static function index(): void {
		global $templateData;

		$templateData = [];

		// Populate item and location selects.
		$templateData['items']     = get_posts(
			[
				'post_type'      => ItemCPT::$postType,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		$templateData['locations'] = get_posts(
			[
				'post_type'      => LocationCPT::$postType,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		// Timeframe type options (bookable / closed etc.).
		$templateData['timeframeTypes'] = TimeframeCPT::getTypesforSelectField();

		// Repetition options.
		$templateData['repetitionOptions'] = TimeframeCPT::getTimeFrameRepetitions();

		// Grid options.
		$templateData['gridOptions'] = TimeframeCPT::getGridOptions();

		ob_start();
		commonsbooking_sanitizeHTML( commonsbooking_get_template_part( 'availabilitywizard', 'index' ) );
		echo ob_get_clean();
	}

	/**
	 * AJAX handler: quickly create an Item or Location post.
	 *
	 * Expects POST fields:
	 *   _ajax_nonce  string  WP nonce (action: cb_availability_wizard)
	 *   post_type    string  One of: cb_item, cb_location
	 *   post_title   string  The post title
	 *   street       string  (Location only) street address
	 *   postcode     string  (Location only) postal code
	 *   city         string  (Location only) city
	 *
	 * @return void  Sends JSON and terminates.
	 */
	public static function ajaxCreatePost(): void {
		check_ajax_referer( 'cb_availability_wizard', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_' . COMMONSBOOKING_PLUGIN_SLUG ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'commonsbooking' ) ], 403 );
		}

		$postType = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';

		if ( ! in_array( $postType, self::$allowedPostTypes, true ) || 'cb_timeframe' === $postType ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post type.', 'commonsbooking' ) ], 400 );
		}

		$postTitle = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';

		if ( '' === $postTitle ) {
			wp_send_json_error( [ 'message' => __( 'Title is required.', 'commonsbooking' ) ], 400 );
		}

		$newPostId = wp_insert_post(
			[
				'post_type'   => $postType,
				'post_title'  => $postTitle,
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $newPostId ) ) {
			wp_send_json_error( [ 'message' => $newPostId->get_error_message() ], 500 );
		}

		// Store address meta for locations.
		if ( 'cb_location' === $postType ) {
			$street   = isset( $_POST['street'] ) ? sanitize_text_field( wp_unslash( $_POST['street'] ) ) : '';
			$postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
			$city     = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';

			update_post_meta( $newPostId, COMMONSBOOKING_METABOX_PREFIX . 'location_street', $street );
			update_post_meta( $newPostId, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', $postcode );
			update_post_meta( $newPostId, COMMONSBOOKING_METABOX_PREFIX . 'location_city', $city );
		}

		wp_send_json_success(
			[
				'id'    => $newPostId,
				'title' => get_the_title( $newPostId ),
			]
		);
	}

	/**
	 * AJAX handler: create a Timeframe with all meta from the wizard's final step.
	 *
	 * Expects POST fields:
	 *   _ajax_nonce       string  WP nonce (action: cb_availability_wizard)
	 *   post_title        string  Timeframe title
	 *   item_id           int     Linked item post ID
	 *   location_id       int     Linked location post ID
	 *   type              int     Timeframe type (see TimeframeCPT constants)
	 *   start_date        string  Start date (YYYY-MM-DD)
	 *   end_date          string  End date   (YYYY-MM-DD), optional
	 *   full_day          string  'on' | ''
	 *   start_time        string  HH:MM, optional
	 *   end_time          string  HH:MM, optional
	 *   repetition        string  Repetition type key
	 *   grid              string  Grid type key
	 *
	 * @return void  Sends JSON and terminates.
	 */
	public static function ajaxCreateTimeframe(): void {
		check_ajax_referer( 'cb_availability_wizard', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_' . COMMONSBOOKING_PLUGIN_SLUG ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'commonsbooking' ) ], 403 );
		}

		$postTitle  = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
		$itemId     = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$locationId = isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
		$type       = isset( $_POST['type'] ) ? absint( $_POST['type'] ) : 0;
		$startDate  = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$endDate    = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
		$fullDay    = isset( $_POST['full_day'] ) ? sanitize_text_field( wp_unslash( $_POST['full_day'] ) ) : '';
		$startTime  = isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '';
		$endTime    = isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : '';
		$repetition = isset( $_POST['repetition'] ) ? sanitize_text_field( wp_unslash( $_POST['repetition'] ) ) : 'norep';
		$grid       = isset( $_POST['grid'] ) ? sanitize_text_field( wp_unslash( $_POST['grid'] ) ) : '0';

		if ( '' === $postTitle ) {
			wp_send_json_error( [ 'message' => __( 'Title is required.', 'commonsbooking' ) ], 400 );
		}

		if ( ! $itemId || ! $locationId ) {
			wp_send_json_error( [ 'message' => __( 'Item and Location are required.', 'commonsbooking' ) ], 400 );
		}

		if ( '' === $startDate ) {
			wp_send_json_error( [ 'message' => __( 'Start date is required.', 'commonsbooking' ) ], 400 );
		}

		$startTs = strtotime( $startDate . ' 00:00:00' );
		$endTs   = $endDate ? strtotime( $endDate . ' 00:00:00' ) : 0;

		if ( false === $startTs ) {
			wp_send_json_error( [ 'message' => __( 'Invalid start date.', 'commonsbooking' ) ], 400 );
		}

		$newPostId = wp_insert_post(
			[
				'post_type'   => TimeframeCPT::$postType,
				'post_title'  => $postTitle,
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $newPostId ) ) {
			wp_send_json_error( [ 'message' => $newPostId->get_error_message() ], 500 );
		}

		// Core linkage meta.
		update_post_meta( $newPostId, Timeframe::META_ITEM_ID, $itemId );
		update_post_meta( $newPostId, Timeframe::META_ITEM_SELECTION_TYPE, Timeframe::SELECTION_MANUAL_ID );
		update_post_meta( $newPostId, Timeframe::META_LOCATION_ID, $locationId );
		update_post_meta( $newPostId, Timeframe::META_LOCATION_SELECTION_TYPE, Timeframe::SELECTION_MANUAL_ID );

		// Timeframe type.
		update_post_meta( $newPostId, 'type', $type );

		// Date range.
		update_post_meta( $newPostId, Timeframe::REPETITION_START, $startTs );
		if ( $endTs ) {
			update_post_meta( $newPostId, Timeframe::REPETITION_END, $endTs );
		}

		// Full-day / time slots.
		update_post_meta( $newPostId, 'full-day', 'on' === $fullDay ? 'on' : '' );
		if ( $startTime ) {
			update_post_meta( $newPostId, 'start-time', $startTime );
		}
		if ( $endTime ) {
			update_post_meta( $newPostId, 'end-time', $endTime );
		}

		// Repetition & grid.
		update_post_meta( $newPostId, Timeframe::META_REPETITION, $repetition );
		update_post_meta( $newPostId, 'grid', $grid );

		wp_send_json_success(
			[
				'id'       => $newPostId,
				'redirect' => admin_url( 'admin.php?page=cb-availability' ),
			]
		);
	}
}
