<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

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
		$this->assertEquals( get_option( 'time_format' ), 'H:i');
		$this->assertEquals( get_option( 'date_format' ), 'd.m.Y');
	}

	/**
	 * @after
	 */
	protected function tearDown_FormatDeLocaleFormatConfiguration() {
		update_option( 'time_format', $this->old_tfmt );
		update_option( 'date_format', $this->old_dfmt );
	}

	protected function setUp() : void {

		parent::setUp();

		$this->restrictionWithoutEndDateId = parent::createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			null
		);

		$this->restrictionWithEndDateId = parent::createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			strtotime("+3 weeks", strtotime(self::CURRENT_DATE))
		);

	}

	public function testIsValid() {
		$restriction = new Restriction($this->restrictionWithoutEndDateId);
		$this->assertTrue( $restriction->isValid() );

		$restriction = new Restriction($this->restrictionWithEndDateId);
		$this->assertTrue( $restriction->isValid() );

		$restriction = new Restriction($this->restrictionForEverything);
		$this->assertTrue( $restriction->isValid() );

		$restrictionNoItem = new Restriction(
			$this->createRestriction(
				Restriction::TYPE_HINT,
				$this->locationId,
				"",
				strtotime(self::CURRENT_DATE),
				strtotime("+3 weeks", strtotime(self::CURRENT_DATE))
			)
		);
		try {
			$restrictionNoItem->isValid();
			$this->fail("Expected exception not thrown");
		} catch (\Exception $e) {
			$this->assertStringContainsString("No item selected", $e->getMessage());
		}

		$restrictionNoLocation = new Restriction(
			$this->createRestriction(
				Restriction::TYPE_HINT,
				"",
				$this->itemId,
				strtotime(self::CURRENT_DATE),
				strtotime("+3 weeks", strtotime(self::CURRENT_DATE))
			)
		);
		try {
			$restrictionNoLocation->isValid();
			$this->fail("Expected exception not thrown");
		} catch (\Exception $e) {
			$this->assertStringContainsString("No location selected", $e->getMessage());
		}

		$restrictionDatesWrong = new Restriction(
			$this->createRestriction(
				Restriction::TYPE_HINT,
				$this->locationId,
				$this->itemId,
				strtotime("+2 months", strtotime(self::CURRENT_DATE)),
				strtotime("+1 months", strtotime(self::CURRENT_DATE))
			)
		);
		try {
			$restrictionDatesWrong->isValid();
			$this->fail("Expected exception not thrown");
		} catch (\Exception $e) {
			$this->assertStringContainsString("Start date is after end date", $e->getMessage());
		}
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testGetEndDate() {
		$restrictionWithoutEndDate = new Restriction($this->restrictionWithoutEndDateId);
		$this->assertTrue($restrictionWithoutEndDate->getStartDate() == strtotime(self::CURRENT_DATE));
		$this->assertFalse($restrictionWithoutEndDate->hasEnddate());
		$this->assertTrue($restrictionWithoutEndDate->getEndDate() === Restriction::NO_END_TIMESTAMP);

		$restrictionWithEndDate = new Restriction($this->restrictionWithEndDateId);
		$this->assertTrue($restrictionWithEndDate->getStartDate() == strtotime(self::CURRENT_DATE));
		$this->assertTrue($restrictionWithEndDate->getEndDate() == strtotime("+3 weeks", strtotime(self::CURRENT_DATE)));
		$this->assertTrue($restrictionWithEndDate->hasEnddate());
	}

	public function testGetFormattedDateTime() {
		$restrictionWithoutEndDate = new Restriction($this->restrictionWithoutEndDateId);
		$this->assertEquals( $restrictionWithoutEndDate->getFormattedStartDateTime(), "01.07.2021 00:00" );
		// End Date is null, therefore getFormattedEndTime() won't compute a string

		$restrictionWithEndDate = new Restriction($this->restrictionWithEndDateId);
		$this->assertEquals( $restrictionWithEndDate->getFormattedStartDateTime(), "01.07.2021 00:00");
		$this->assertEquals( $restrictionWithEndDate->getFormattedEndDateTime(),   "22.07.2021 00:00");

	}
}
