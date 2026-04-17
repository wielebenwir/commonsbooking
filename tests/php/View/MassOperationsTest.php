<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\MassOperations;

class MassOperationsTest extends CustomPostTypeTest {

	public function testRenderOrphanedMigrationButton() {
		ob_start();
		MassOperations::renderOrphanedMigrationButton();
		$html = ob_get_clean();
		// naive way of testing html validity
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$this->assertTrue( $doc->loadHTML( $html ) );
		$this->assertEquals( 0, count( libxml_get_errors() ) );
	}

	public function testRenderBookingViewTable() {
		// first with empty result
		ob_start();
		MassOperations::renderBookingViewTable( [] );
		$html = ob_get_clean();
		$this->assertStringContainsString( '<p>No bookings found.</p>', $html );

		// then with a booking
		$booking = new Booking( $this->createConfirmedBookingEndingToday() );
		ob_start();
		MassOperations::renderBookingViewTable( [ $booking ] );
		$html = ob_get_clean();
		// naive way of testing html validity
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$this->assertTrue( $doc->loadHTML( $html ) );
		$this->assertEquals( 0, count( libxml_get_errors() ) );
	}

	protected function setUp(): void {
		parent::setUp();
	}
}
