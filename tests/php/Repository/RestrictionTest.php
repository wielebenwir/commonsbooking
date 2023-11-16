<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use PHPUnit\Framework\TestCase;

class RestrictionTest extends CustomPostTypeTest
{

	protected $timeframeId;
	protected $restrictionId;

	/**
	 * We cannot test the getting for items and locations separately, because the filterPosts method in the restriction repository will filter
	 * out queries where we are not searching for both the item and the corresponding location.
	 *
	 * @see \CommonsBooking\Repository\Restriction::filterPosts()
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testGetByItemAndLocation()
	{
	    $restrictions = Restriction::get( [$this->locationId], [$this->itemId]);
	    $this->assertIsArray( $restrictions );
	    $this->assertEquals( 1, count( $restrictions ) );
	    $this->assertEquals( $this->restrictionId, $restrictions[0]->ID );
	}



	protected function setUp(): void {
		parent::setUp();
		$this->timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->restrictionId = 	$this->createRestriction(
			"hint",
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			strtotime("+1 day", strtotime(self::CURRENT_DATE))
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
