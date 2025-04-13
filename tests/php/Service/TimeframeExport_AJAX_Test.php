<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\TimeframeExport;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TimeframeExport_AJAX_Test extends \WP_Ajax_UnitTestCase {

	private $itemID;
	private $locationID;
	private $bookingIDs = [];


	public function testAjaxExport_empty() {
		// first case: empty bookings
		try {
			$this->_handleAjax( 'cb_export_timeframes' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		$response = $this->cleanResponse( $this->_last_response );
		$this->assertFalse( $response->success );
		$this->assertTrue( $response->error );
		$this->assertEquals( 'No data was found for the selected time period', $response->message );
	}

	public function testAjaxExport_twoBookings() {
		// create two bookings
		$firstBooking  = $this->createBooking(
			strtotime( CustomPostTypeTest::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( CustomPostTypeTest::CURRENT_DATE ) )
		);
		$secondBooking = $this->createBooking(
			strtotime( '+2 days', strtotime( CustomPostTypeTest::CURRENT_DATE ) ),
			strtotime( '+3 days', strtotime( CustomPostTypeTest::CURRENT_DATE ) )
		);
		try {
			$this->_handleAjax( 'cb_export_timeframes' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		$response = $this->cleanResponse( $this->_last_response );
		$this->assertTrue( $response->success );
		$this->assertFalse( $response->error );
		$this->assertEquals( 'Export finished', $response->message );
		$stdObjects = TimeframeExportTest::csvStringToStdObjects( $response->csv );
		$this->assertEquals( 2, count( $stdObjects ) );
		$this->assertEqualsCanonicalizing(
			[ $firstBooking, $secondBooking ],
			array_map(
				function ( $stdObject ) {
					return $stdObject->ID;
				},
				$stdObjects
			)
		);
	}

	public function testAjaxExport_Paginated() {
		// create ITERATION_COUNT + 1 bookings so that we have to paginate
		$totalBookings = TimeframeExport::ITERATION_COUNTS + 1;
		for ( $i = 0; $i < $totalBookings; $i++ ) {
			$this->createBooking(
				strtotime( '+ ' . $i . ' days', strtotime( CustomPostTypeTest::CURRENT_DATE ) ),
				strtotime( '+ ' . ( $i + 1 ) . ' days', strtotime( CustomPostTypeTest::CURRENT_DATE ) )
			);
		}
		try {
			$this->_handleAjax( 'cb_export_timeframes' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		$rawFirstPageResponse = $this->_last_response;
		$firstPageResponse    = $this->cleanResponse( $rawFirstPageResponse );
		$this->assertFalse( $firstPageResponse->success );
		$this->assertFalse( $firstPageResponse->error );
		$this->assertEquals( 'Processed ' . TimeframeExport::ITERATION_COUNTS . ' of ' . $totalBookings . ' bookings', $firstPageResponse->progress );
		$tfs = $firstPageResponse->settings->relevantTimeframes;
		$this->assertEquals( TimeframeExport::ITERATION_COUNTS, count( $tfs ) );

		// let's set the $_POST data anew so that we can get the second page
		// we do the decoding and encoding to get an associative array from an object
		$_POST['data'] = json_decode( json_encode( $firstPageResponse ), true );
		// not sure if this is necessary, but let's set the nonce anew
		$_POST['_wpnonce'] = wp_create_nonce( 'cb_export_timeframes' );
		try {
			$this->_handleAjax( 'cb_export_timeframes' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}
		$allResponses       = $this->_last_response;
		$secondPageResponse = $this->cleanResponse( $this->_last_response, $rawFirstPageResponse );
		$this->assertTrue( $secondPageResponse->success );
		$this->assertFalse( $secondPageResponse->error );
		$this->assertEquals( 'Export finished', $secondPageResponse->message );
		$stdObjects = TimeframeExportTest::csvStringToStdObjects( $secondPageResponse->csv );
		$this->assertEquals( $totalBookings, count( $stdObjects ) );
		$this->assertEqualsCanonicalizing(
			$this->bookingIDs,
			array_map(
				function ( $stdObject ) {
					return $stdObject->ID;
				},
				$stdObjects
			)
		);
	}

	public function set_up() {
		parent::set_up();
		add_action(
			'wp_ajax_cb_export_timeframes',
			array(
				\CommonsBooking\Service\TimeframeExport::class,
				'ajaxExportCsv',
			)
		);
		$currentDateNextMonth = new \DateTime( CustomPostTypeTest::CURRENT_DATE );
		$currentDateNextMonth->modify( '+2 years' );
		$currentDateNextMonth = $currentDateNextMonth->format( 'd.m.Y' );
		$_POST['_wpnonce']    = wp_create_nonce( 'cb_export_timeframes' );
		$exportSettings       = array(
			'exportType'      => 'all',
			'exportStartDate' => CustomPostTypeTest::CURRENT_DATE,
			'exportEndDate'   => $currentDateNextMonth,
			'locationFields'  => '',
			'itemFields'      => '',
			'userFields'      => '',
		);
		$progressText         = '0/0 bookings exported';
		$_POST['data']        = [
			'settings' => $exportSettings,
			'progress' => $progressText,
		];

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

	private function createBooking(
		int $start,
		int $end
	): int {
		// Create booking
		$bookingId = wp_insert_post(
			[
				'post_title'  => 'Booking',
				'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				'post_status' => 'confirmed',
				'post_author' => '0',
			]
		);

		update_post_meta( $bookingId, 'type', \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID );
		update_post_meta( $bookingId, 'timeframe-repetition', 'd' );
		update_post_meta( $bookingId, 'start-time', '08:00 AM' );
		update_post_meta( $bookingId, 'end-time', '12:00 PM' );
		update_post_meta( $bookingId, 'timeframe-max-days', 3 );
		update_post_meta( $bookingId, 'location-id', $this->locationID );
		update_post_meta( $bookingId, 'item-id', $this->itemID );
		update_post_meta( $bookingId, 'grid', 0 );
		update_post_meta( $bookingId, 'repetition-start', $start );
		update_post_meta( $bookingId, 'repetition-end', $end );
		update_post_meta( $bookingId, 'weekdays', [] );

		$this->bookingIDs[] = $bookingId;

		return $bookingId;
	}

	public function tear_down() {
		parent::tear_down();
		remove_action(
			'wp_ajax_cb_export_timeframes',
			array(
				\CommonsBooking\Service\TimeframeExport::class,
				'ajaxExportCsv',
			)
		);
		wp_delete_post( $this->itemID, true );
		wp_delete_post( $this->locationID, true );
		foreach ( $this->bookingIDs as $bookingID ) {
			wp_delete_post( $bookingID, true );
		}
	}

	/**
	 * Remove deprecation warnings and whitespaces. Returns JSON decoded object.
	 *
	 * @param string $input
	 * @param string $previousResponses
	 *
	 * @return mixed
	 */
	public function cleanResponse( string $input, string $previousResponses = '' ) {
		// trim away previous responses because they are just appended to the current response
		if ( $previousResponses ) {
			$input = substr( $input, strlen( $previousResponses ) );
		}
		$cleanedInput = preg_replace( '/^Deprecated:.*$/m', '', $input );
		$cleanedInput = trim( $cleanedInput );

		return json_decode( $cleanedInput );
	}
}
