<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\TimeframeExport;
use CommonsBooking\Tests\Wordpress\CustomPostType_AJAX_Test;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TimeframeExport_AJAX_Test extends CustomPostType_AJAX_Test {

	protected $hooks = [
		'cb_export_timeframes' => array(
			\CommonsBooking\Service\TimeframeExport::class,
			'ajaxExportCsv',
		),
	];
	public function testAjaxExport_empty() {
		// first case: empty bookings
		$response = $this->runHook();
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
		$response      = $this->runHook();
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
		$firstPageResponse = $this->runHook();
		$this->assertFalse( $firstPageResponse->success );
		$this->assertFalse( $firstPageResponse->error );
		$this->assertEquals( 'Processed ' . TimeframeExport::ITERATION_COUNTS . ' of ' . $totalBookings . ' bookings', $firstPageResponse->progress );

		// let's set the $_POST data anew so that we can get the second page
		// we do the decoding and encoding to get an associative array from an object
		$_POST['data'] = json_decode( json_encode( $firstPageResponse ), true );
		// not sure if this is necessary, but let's set the nonce anew
		$_POST['_wpnonce']  = wp_create_nonce( 'cb_export_timeframes' );
		$secondPageResponse = $this->runHook();
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
		$currentDateNextMonth = new \DateTime( CustomPostTypeTest::CURRENT_DATE );
		$currentDateNextMonth->modify( '+2 years' );
		$currentDateNextMonth = $currentDateNextMonth->format( 'd.m.Y' );
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
	}
}
