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
		$this->assertTrue ( MassOperations::migrateOrphaned( [ $toOrphan->ID ] ) );

		$this->assertFalse( $toOrphan->isOrphaned() );
		$this->assertEquals( $newLocation, get_post_meta( $toOrphan->ID, 'location-id', true ) );
		$this->expectExceptionMessage( 'No bookings to move selected.' );
		//empty array given
		MassOperations::migrateOrphaned( [] );
	}

	public function testMigrateOrphanedWithExistingBooking() {
		//test case #1695: there is already an existing booking on the new location
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		//create an orphaned booking
		$toOrphan    = new Booking( $this->createConfirmedBookingStartingToday() );
		$newLocation = $this->createLocation( "New Location", 'publish' );
		update_post_meta( $this->testTimeframe->ID, 'location-id', $newLocation );

		//create a booking on the new location (in the place where we try to move the orphan later)
		$existingBooking = new Booking( $this->createConfirmedBookingStartingToday($newLocation) );

		$orphans = \CommonsBooking\Repository\Booking::getOrphaned();
		$this->assertCount( 1, $orphans );
		$this->expectExceptionMessage("There is already a booking on the new location during the timeframe of an existing booking.");
		MassOperations::migrateOrphaned( [ $toOrphan->ID ] );
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
