<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Service\MassOperations;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class MassOperationsTest extends CustomPostTypeTest {
	private Timeframe $testTimeframe;

	public function testMigrateOrphaned() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		//create an orphaned booking
		$toOrphan    = new Booking( $this->createConfirmedBookingStartingToday() );
		$newLocation = $this->createLocation( "New Location", 'publish' );
		update_post_meta( $this->testTimeframe->ID, 'location-id', $newLocation );

		$this->assertTrue( $toOrphan->isOrphaned() );
		$orphans = \CommonsBooking\Repository\Booking::getOrphaned();
		$this->assertCount( 1, $orphans );
		MassOperations::migrateOrphaned( [ $toOrphan->ID ] );
		$this->assertFalse( $toOrphan->isOrphaned() );
		$this->assertEquals( $newLocation, get_post_meta( $toOrphan->ID, 'location-id', true ) );

		//empty array given
		$result = MassOperations::migrateOrphaned( [] );
		$this->assertFalse( $result['success'] );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->testTimeframe = new Timeframe(
			$this->createBookableTimeFrameIncludingCurrentDay()
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
