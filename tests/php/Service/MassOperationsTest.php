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
		$orphanMove  = new Booking( $this->createConfirmedBookingStartingToday() ); //this booking should be moved
		$orphanStay  = new Booking( $this->createConfirmedBookingEndingToday() ); //this booking should stay
		$newLocation = $this->createLocation( "New Location", 'publish' );
		update_post_meta( $this->testTimeframe->ID, 'location-id', $newLocation );

		//create new booking on new location that does not collide and is not an orphan
		$noOrphan = new Booking( $this->createBooking( $newLocation,
			$this->itemId,
			strtotime( '+7 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+8 days' ), strtotime( self::CURRENT_DATE ) )
		);

		$this->assertTrue( $orphanMove->isOrphaned() );
		$this->assertTrue( $orphanStay->isOrphaned() );
		$orphans = \CommonsBooking\Repository\Booking::getOrphaned();
		$this->assertCount( 2, $orphans );
		$this->assertTrue( MassOperations::migrateOrphaned( [ $orphanMove->ID ] ) );

		$this->assertFalse( $orphanMove->isOrphaned() );
		$this->assertEquals( $newLocation, get_post_meta( $orphanMove->ID, 'location-id', true ) );
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
		new Booking( $this->createConfirmedBookingStartingToday( $newLocation ) );

		$orphans = \CommonsBooking\Repository\Booking::getOrphaned();
		$this->assertCount( 1, $orphans );
		$this->expectExceptionMessage( "There is already a booking on the new location during the timeframe of booking with ID " . $toOrphan->ID );
		MassOperations::migrateOrphaned( [ $toOrphan->ID ] );
	}

	public function testMigrateOrphanedNoValidLocation() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		//create an orphaned booking
		$toOrphan            = new Booking( $this->createConfirmedBookingStartingToday() );
		$nonExistingLocation = '123456';
		update_post_meta( $this->testTimeframe->ID, 'location-id', $nonExistingLocation );

		$orphans = \CommonsBooking\Repository\Booking::getOrphaned();
		$this->assertCount( 1, $orphans );
		$this->expectExceptionMessage( "New location not found for booking with ID" );
		MassOperations::migrateOrphaned( [ $toOrphan->ID ] );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->testTimeframe = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+14 days', strtotime( self::CURRENT_DATE ) )
			)
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
