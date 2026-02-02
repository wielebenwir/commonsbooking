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

	/**
	 * @group failing
	 */
	public function testGetFormattedDateTime() {
		$restrictionWithoutEndDate = new Restriction( $this->restrictionWithoutEndDateId );
		$this->assertEquals( '01.07.2021 00:00', $restrictionWithoutEndDate->getFormattedStartDateTime() );
		// End Date is null, therefore getFormattedEndTime() won't compute a string

		$restrictionWithEndDate = new Restriction( $this->restrictionWithEndDateId );
		$this->assertEquals( '01.07.2021 00:00', $restrictionWithEndDate->getFormattedStartDateTime() );
		$this->assertEquals( '22.07.2021 00:00', $restrictionWithEndDate->getFormattedEndDateTime() );
	}

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
}
