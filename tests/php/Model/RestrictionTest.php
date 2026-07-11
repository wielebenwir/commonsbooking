<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class RestrictionTest extends CustomPostTypeTest {

	/**
	 * @var Restriction
	 */
	private $restrictionWithEndDateId;

	/**
	 * @var Restriction
	 */
	private $restrictionWithoutEndDateId;

	private $old_tfmt;
	private $old_dfmt;

	/**
	 * @before
	 */
	protected function setUp_FormatDeLocaleFormatConfiguration() {
		$this->old_tfmt = get_option( 'time_format' );
		$this->old_dfmt = get_option( 'date_format' );
		update_option( 'time_format', 'H:i' );
		update_option( 'date_format', 'd.m.Y' );
		$this->assertEquals( get_option( 'time_format' ), 'H:i' );
		$this->assertEquals( get_option( 'date_format' ), 'd.m.Y' );
	}

	/**
	 * @after
	 */
	protected function tearDown_FormatDeLocaleFormatConfiguration() {
		update_option( 'time_format', $this->old_tfmt );
		update_option( 'date_format', $this->old_dfmt );
	}

	protected function setUp(): void {

		parent::setUp();

		$this->restrictionWithoutEndDateId = parent::createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null
		);

		$this->restrictionWithEndDateId = parent::createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+3 weeks', strtotime( self::CURRENT_DATE ) )
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetEndDate() {
		$restrictionWithoutEndDate = new Restriction( $this->restrictionWithoutEndDateId );
		$this->assertTrue( $restrictionWithoutEndDate->getStartDate() == strtotime( self::CURRENT_DATE ) );
		$this->assertFalse( $restrictionWithoutEndDate->hasEnddate() );
		$this->assertTrue( $restrictionWithoutEndDate->getEndDate() === Restriction::NO_END_TIMESTAMP );

		$restrictionWithEndDate = new Restriction( $this->restrictionWithEndDateId );
		$this->assertTrue( $restrictionWithEndDate->getStartDate() == strtotime( self::CURRENT_DATE ) );
		$this->assertTrue( $restrictionWithEndDate->getEndDate() == strtotime( '+3 weeks', strtotime( self::CURRENT_DATE ) ) );
		$this->assertTrue( $restrictionWithEndDate->hasEnddate() );
	}

	public function testGetFormattedDateTime() {
		$restrictionWithoutEndDate = new Restriction( $this->restrictionWithoutEndDateId );
		$this->assertEquals( $restrictionWithoutEndDate->getFormattedStartDateTime(), '01.07.2021 00:00' );
		// End Date is null, therefore getFormattedEndTime() won't compute a string

		$restrictionWithEndDate = new Restriction( $this->restrictionWithEndDateId );
		$this->assertEquals( $restrictionWithEndDate->getFormattedStartDateTime(), '01.07.2021 00:00' );
		$this->assertEquals( $restrictionWithEndDate->getFormattedEndDateTime(), '22.07.2021 00:00' );
	}

	public function testGetAdmins() {
		// case 1 : no admins set.
		// Should just return author
		$restriction = new Restriction( $this->restrictionWithoutEndDateId );
		$this->assertEquals( [ self::USER_ID ], $restriction->getAdmins() );

		// Case 2: Just item admin set.
		// Should return author + item admin
		$this->createCBManager();
		$managedItem       = $this->createItem( 'Managed Item', 'publish', [ $this->cbManagerUserID ] );
		$unmanagedLocation = $this->createLocation( 'Unmanaged Location' );
		$restriction       = new Restriction(
			$this->createRestriction(
				Restriction::META_HINT,
				$unmanagedLocation,
				$managedItem,
				strtotime( self::CURRENT_DATE ),
				null
			)
		);
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $restriction->getAdmins() );

		// Case 3: Just location admin set.
		// Should return author + location admin
		$managedLocation = $this->createLocation( 'Managed Location', 'publish', [ $this->cbManagerUserID ] );
		$unmanagedItem   = $this->createItem( 'Unmanaged Item' );
		$restriction     = new Restriction(
			$this->createRestriction(
				Restriction::META_HINT,
				$managedLocation,
				$unmanagedItem,
				strtotime( self::CURRENT_DATE ),
				null
			)
		);
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $restriction->getAdmins() );

		// Case 4: both item and location admin set.
		// Should return author + admin (no duplicates)
		$otherManagedLocation = $this->createLocation( 'Other Managed Location', 'publish', [ $this->cbManagerUserID ] );
		$otherManagedItem     = $this->createItem( 'Other Managed Item', 'publish', [ $this->cbManagerUserID ] );
		$restriction          = new Restriction(
			$this->createRestriction(
				Restriction::META_HINT,
				$otherManagedLocation,
				$otherManagedItem,
				strtotime( self::CURRENT_DATE ),
				null
			)
		);
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $restriction->getAdmins() );
	}
}
