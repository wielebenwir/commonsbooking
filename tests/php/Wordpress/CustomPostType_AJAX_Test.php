<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Model\Timeframe;

/**
 * The base class for all AJAX tests on CPTs to inherit from.
 * When implementing a new AJAX test, you NEED to implement $hooks.
 * Use the runHook method to run the AJAX call.
 */
abstract class CustomPostType_AJAX_Test extends \WP_Ajax_UnitTestCase {

	/**
	 * @array
	 * The hooks and callback functions as an associative array.
	 * The key is the name of the hook, the value is an array with the class and method name of the callback function.
	 */
	protected $hooks;
	/**
	 * @int The ID of the item that is always created.
	 */
	protected $itemID;
	/**
	 * @int The ID of the location that is always created.
	 */
	protected $locationID;
	/**
	 * @int The ID of the timeframe that is created when running createTimeframe.
	 */
	protected $timeframeID;
	/**
	 * @int array The IDs of all created bookings. Append here, if you should create one.
	 */
	protected $bookingIDs = [];
	/**
	 * @int array The IDs of all created timeframes. Append here, if you should create one.
	 */
	protected $timeframeIDs   = [];
	private $previousResponse = '';
	public function set_up() {
		parent::set_up();
		foreach ( $this->hooks as $hookName => $callback ) {
			add_action( 'wp_ajax_' . $hookName, $callback );
		}
		// create items and locations. We can't use the functions from the CustomPostTypeTest class because this class extends WP_Ajax_UnitTestCase
		$this->itemID     = wp_insert_post(
			[
				'post_title'  => 'AJAX Test Item',
				'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
				'post_status' => 'publish',
			]
		);
		$this->locationID = wp_insert_post(
			[
				'post_title'  => 'AJAX Test Location',
				'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
				'post_status' => 'publish',
			]
		);
	}
	public function tear_down() {
		parent::tear_down();
		foreach ( array_keys( $this->hooks ) as $hook ) {
			remove_action( 'wp_ajax_' . $hook, $this->hooks[ $hook ] );
		}
		wp_delete_post( $this->itemID, true );
		wp_delete_post( $this->locationID, true );
		foreach ( $this->timeframeIDs as $timeframeID ) {
			wp_delete_post( $timeframeID, true );
		}
		foreach ( $this->bookingIDs as $bookingID ) {
			wp_delete_post( $bookingID, true );
		}
	}

	/**
	 * @return int|\WP_Error
	 */
	public function createTimeframe() {
		$this->timeframeID    = wp_insert_post(
			[
				'post_title' => 'AJAX Test Timeframe',
				'post_type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
				'post_status' => 'publish',
				'post_author' => 1,
				'meta_input' => [
					'type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
					'location-id' => $this->locationID,
					'item-id' => $this->itemID,
					'timeframe-max-days' => 3,
					'timeframe-advance-booking-days' => 30,
					'booking-startday-offset' => 0,
					'full-day' => 'on',
					\CommonsBooking\Model\Timeframe::META_REPETITION => 'd',
					'repetition-start' => strtotime( CustomPostTypeTest::CURRENT_DATE ),
					'repetition-end' => strtotime( '+2 weeks', strtotime( CustomPostTypeTest::CURRENT_DATE ) ),
					'start-time' => '8:00 AM',
					'end-time' => '12:00 PM',
					'grid' => 0,
					Timeframe::META_CREATE_BOOKING_CODES => 'on',
					Timeframe::META_SHOW_BOOKING_CODES => 'on',
				],
			]
		);
		$this->timeframeIDs[] = $this->timeframeID;
		return $this->timeframeID;
	}

	protected function createBooking(
		int $start,
		int $end
	): int {
		// Create booking
		$bookingID = wp_insert_post(
			[
				'post_title'  => 'Booking',
				'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				'post_status' => 'confirmed',
				'post_author' => '0',
				'meta_input'  => [
					'type' => \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID,
					'location-id' => $this->locationID,
					'item-id' => $this->itemID,
					Timeframe::META_REPETITION => 'd',
					'start-time' => '08:00 AM',
					'end-time' => '12:00 PM',
					'timeframe-max-days' => 3,
					'grid' => 0,
					'repetition-start' => $start,
					'repetition-end' => $end,
					'weekdays' => [],
				],
			]
		);

		$this->bookingIDs[] = $bookingID;

		return $bookingID;
	}

	/**
	 * Remove deprecation warnings and whitespaces. Returns JSON decoded object.
	 *
	 * @param string $input
	 * @param string $previousResponses
	 *
	 * @return mixed
	 */
	public static function cleanResponse( string $input, string $previousResponses = '' ) {
		// trim away previous responses because they are just appended to the current response
		if ( $previousResponses ) {
			$input = substr( $input, strlen( $previousResponses ) );
		}
		$cleanedInput = preg_replace( '/^Deprecated:.*$/m', '', $input );
		$cleanedInput = trim( $cleanedInput );

		return json_decode( $cleanedInput );
	}

	/**
	 * Runs an AJAX call and returns the response in a cleaned up format.
	 * @return mixed
	 */
	public function runHook( $hookName = null ) {
		// if no hook name is given, we take the first
		if ( $hookName === null ) {
			$hookName = array_key_first( $this->hooks );
		}
		$_POST['_wpnonce'] = wp_create_nonce( $hookName );
		try {
			$this->_handleAjax( $hookName );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		$response               = self::cleanResponse( $this->_last_response, $this->previousResponse );
		$this->previousResponse = $this->_last_response;
		return $response;
	}
}
