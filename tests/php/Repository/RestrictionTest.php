<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

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

	public function testGetSetAll() {
		$allRestriction = new \CommonsBooking\Model\Restriction(
			$this->createRestriction(
				\CommonsBooking\Model\Restriction::TYPE_REPAIR,
				CustomPostType::SELECTION_ALL_POSTS,
				CustomPostType::SELECTION_ALL_POSTS,
				strtotime(self::CURRENT_DATE),
				strtotime("+1 day", strtotime(self::CURRENT_DATE)),
			)
		);
		$restrictions = Restriction::get( [$this->locationId], [$this->itemId],null,true);
		//make sure that we get both restrictions
		$this->assertEquals( 2, count( $restrictions ) );
		$restrictionIds = array_map( function( $restriction ) {
			return $restriction->ID;
		}, $restrictions );
		$this->assertContains( $allRestriction->ID, $restrictionIds );
		$this->assertContains( $this->restrictionId, $restrictionIds );
	}



	protected function setUp(): void {
		parent::setUp();
		$this->timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->restrictionId = 	$this->createRestriction(
 			\CommonsBooking\Model\Restriction::TYPE_HINT,
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
