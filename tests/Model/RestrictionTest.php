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

	protected function setUp() {
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

	protected function tearDown() {
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

	public function test_get_formatted_datetimes() {

		// TODO maybe this can be generalized into @beforeEach when tests are separated into different methods
		$old_tfmt = get_option( 'time_format' );
		$old_dfmt = get_option( 'date_format' );
		update_option( 'time_format', 'H:i' );
		update_option( 'date_format', 'd.m.Y' );
		$this->assertEquals( get_option( 'time_format' ), 'H:i');
		$this->assertEquals( get_option( 'date_format' ), 'd.m.Y');

		$restrictionWithoutEndDate = new Restriction($this->restrictionWithoutEndDateId);
		$this->assertEquals( $restrictionWithoutEndDate->getFormattedStartDateTime(), "01.07.2021 00:00" );
                $this->assertEquals( $restrictionWithoutEndDate->getFormattedEndDateTime(),   "01.01.1970 00:00" ); // TODO shouldn't null end-dates be handeled differently?

                $restrictionWithEndDate = new Restriction($this->restrictionWithEndDateId);
		$this->assertEquals( $restrictionWithEndDate->getFormattedStartDateTime(), "01.07.2021 00:00");
		$this->assertEquals( $restrictionWithEndDate->getFormattedEndDateTime(),   "22.07.2021 00:00");

		// reverts options, see comment above
		update_option( 'time_format', $old_tfmt );
		update_option( 'date_format', $old_dfmt );
	}
}
