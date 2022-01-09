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
}
